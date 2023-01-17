<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster\Distributor;

/**
 * This class implements an hashring-based distributor that uses the same
 * algorithm of libketama to distribute keys in a cluster using client-side
 * sharding.
 * @author Lorenzo Castelli <lcastelli@gmail.com>
 */
class KetamaRing extends HashRing
{
    public const DEFAULT_REPLICAS = 160;

    /**
     * @param mixed $nodeHashCallback Callback returning a string used to calculate the hash of nodes.
     */
    public function __construct($nodeHashCallback = null)
    {
        parent::__construct($this::DEFAULT_REPLICAS, $nodeHashCallback);
    }

    /**
     * {@inheritdoc}
     */
    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio)
    {
        $nodeObject = $node['object'];
        $nodeHash = $this->getNodeHash($nodeObject);
        $replicas = (int) floor($weightRatio * $totalNodes * ($replicas / 4));

        for ($i = 0; $i < $replicas; ++$i) {
            $unpackedDigest = unpack('V4', md5("$nodeHash-$i", true));

            foreach ($unpackedDigest as $key) {
                $ring[$key] = $nodeObject;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        $hash = unpack('V', md5($value, true));

        return $hash[1];
    }

    /**
     * {@inheritdoc}
     */
    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount)
    {
        // Binary search for the first item in ringkeys with a value greater
        // or equal to the key. If no such item exists, return the first item.
        return $lower < $ringKeysCount ? $lower : 0;
    }
}
