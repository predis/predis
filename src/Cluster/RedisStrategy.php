<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Command\ScriptCommand;

/**
 * Default class used by Predis to calculate hashes out of keys of
 * commands supported by redis-cluster.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisStrategy extends PredisStrategy
{
    /**
     *
     */
    public function __construct(HashGeneratorInterface $hashGenerator = null)
    {
        parent::__construct($hashGenerator ?: new Hash\CRC16());
    }
}
