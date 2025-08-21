<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use Predis\Cluster\Hash\CRC16;
use Predis\Cluster\Hash\HashGeneratorInterface;
use Predis\NotSupportedException;

/**
 * Default class used by Predis to calculate hashes out of keys of
 * commands supported by redis-cluster.
 */
class RedisStrategy extends ClusterStrategy
{
    protected $hashGenerator;

    /**
     * @param HashGeneratorInterface|null $hashGenerator Hash generator instance.
     */
    public function __construct(?HashGeneratorInterface $hashGenerator = null)
    {
        parent::__construct();

        $this->hashGenerator = $hashGenerator ?: new CRC16();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlotByKey($key)
    {
        $key = $this->extractKeyTag($key);

        return $this->hashGenerator->hash($key) & 0x3FFF;
    }

    /**
     * {@inheritdoc}
     */
    public function getDistributor()
    {
        $class = get_class($this);
        throw new NotSupportedException("$class does not provide an external distributor");
    }
}
