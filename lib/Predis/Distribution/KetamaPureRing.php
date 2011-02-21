<?php

namespace Predis\Distribution;

class KetamaPureRing extends HashRing {
    const DEFAULT_REPLICAS = 160;

    public function __construct() {
        parent::__construct($this::DEFAULT_REPLICAS);
    }

    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio) {
        $nodeObject = $node['object'];
        $nodeHash = (string) $nodeObject;
        $replicas = (int) floor($weightRatio * $totalNodes * ($replicas / 4));
        for ($i = 0; $i < $replicas; $i++) {
            $unpackedDigest = unpack('V4', md5("$nodeHash-$i", true));
            foreach ($unpackedDigest as $key) {
                $ring[$key] = $nodeObject;
            }
        }
    }

    public function generateKey($value) {
        $hash = unpack('V', md5($value, true));
        return $hash[1];
    }

    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount) {
        // Binary search for the first item in _ringkeys with a value greater
        // or equal to the key. If no such item exists, return the first item.
        return $lower < $ringKeysCount ? $lower : 0;
    }
}
