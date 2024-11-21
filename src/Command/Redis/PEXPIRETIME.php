<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/pexpiretime/
 *
 * PEXPIRETIME has the same semantic as EXPIRETIME,
 * but returns the absolute Unix expiration timestamp in milliseconds instead of seconds.
 */
class PEXPIRETIME extends RedisCommand
{
    public function getId()
    {
        return 'PEXPIRETIME';
    }
}
