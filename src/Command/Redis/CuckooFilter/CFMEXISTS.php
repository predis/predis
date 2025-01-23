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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cf.mexists/
 *
 * Check if one or more items exists in a Cuckoo Filter key.
 */
class CFMEXISTS extends RedisCommand
{
    public function getId()
    {
        return 'CF.MEXISTS';
    }
}
