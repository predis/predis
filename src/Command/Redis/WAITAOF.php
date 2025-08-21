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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/waitaof/
 *
 * This command blocks the current client until all the previous write commands are acknowledged
 * as having been fsynced to the AOF of the local Redis and/or at least the specified number of replicas.
 */
class WAITAOF extends RedisCommand
{
    public function getId()
    {
        return 'WAITAOF';
    }
}
