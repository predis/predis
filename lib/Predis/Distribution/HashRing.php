<?php

namespace Predis\Distribution;

class HashRing implements IDistributionStrategy {
    const DEFAULT_REPLICAS = 128;
    const DEFAULT_WEIGHT   = 100;
    private $_nodes, $_ring, $_ringKeys, $_ringKeysCount, $_replicas;

    public function __construct($replicas = self::DEFAULT_REPLICAS) {
        $this->_replicas = $replicas;
        $this->_nodes    = array();
    }

    public function add($node, $weight = null) {
        // In case of collisions in the hashes of the nodes, the node added
        // last wins, thus the order in which nodes are added is significant.
        $this->_nodes[] = array('object' => $node, 'weight' => (int) $weight ?: $this::DEFAULT_WEIGHT);
        $this->reset();
    }

    public function remove($node) {
        // A node is removed by resetting the ring so that it's recreated from
        // scratch, in order to reassign possible hashes with collisions to the
        // right node according to the order in which they were added in the
        // first place.
        for ($i = 0; $i < count($this->_nodes); ++$i) {
            if ($this->_nodes[$i]['object'] === $node) {
                array_splice($this->_nodes, $i, 1);
                $this->reset();
                break;
            }
        }
    }

    private function reset() {
        unset($this->_ring);
        unset($this->_ringKeys);
        unset($this->_ringKeysCount);
    }

    private function isInitialized() {
        return isset($this->_ringKeys);
    }

    private function computeTotalWeight() {
        $totalWeight = 0;
        foreach ($this->_nodes as $node) {
            $totalWeight += $node['weight'];
        }
        return $totalWeight;
    }

    private function initialize() {
        if ($this->isInitialized()) {
            return;
        }
        if (count($this->_nodes) === 0) {
            throw new EmptyRingException('Cannot initialize empty hashring');
        }

        $this->_ring = array();
        $totalWeight = $this->computeTotalWeight();
        $nodesCount  = count($this->_nodes);
        foreach ($this->_nodes as $node) {
            $weightRatio = $node['weight'] / $totalWeight;
            $this->addNodeToRing($this->_ring, $node, $nodesCount, $this->_replicas, $weightRatio);
        }
        ksort($this->_ring, SORT_NUMERIC);
        $this->_ringKeys = array_keys($this->_ring);
        $this->_ringKeysCount = count($this->_ringKeys);
    }

    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio) {
        $nodeObject = $node['object'];
        $nodeHash = (string) $nodeObject;
        $replicas = (int) round($weightRatio * $totalNodes * $replicas);
        for ($i = 0; $i < $replicas; $i++) {
            $key = crc32("$nodeHash:$i");
            $ring[$key] = $nodeObject;
        }
    }

    public function generateKey($value) {
        return crc32($value);
    }

    public function get($key) {
        return $this->_ring[$this->getNodeKey($key)];
    }

    private function getNodeKey($key) {
        $this->initialize();
        $ringKeys = $this->_ringKeys;
        $upper = $this->_ringKeysCount - 1;
        $lower = 0;

        while ($lower <= $upper) {
            $index = ($lower + $upper) >> 1;
            $item  = $ringKeys[$index];
            if ($item > $key) {
                $upper = $index - 1;
            }
            else if ($item < $key) {
                $lower = $index + 1;
            }
            else {
                return $item;
            }
        }
        return $ringKeys[$this->wrapAroundStrategy($upper, $lower, $this->_ringKeysCount)];
    }

    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount) {
        // Binary search for the last item in _ringkeys with a value less or
        // equal to the key. If no such item exists, return the last item.
        return $upper >= 0 ? $upper : $ringKeysCount - 1;
    }
}
