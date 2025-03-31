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
 * @see https://redis.io/commands/smismember/
 *
 * Returns whether each member is a member of the set stored at key.
 */
class SMISMEMBER extends RedisCommand
{
    public function getId()
    {
        return 'SMISMEMBER';
    }
}
