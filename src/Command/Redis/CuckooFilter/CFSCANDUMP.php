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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cf.scandump/
 *
 * Begins an incremental save of the cuckoo filter.
 * This is useful for large cuckoo filters which cannot fit into the normal DUMP and RESTORE model.
 */
class CFSCANDUMP extends RedisCommand
{
    public function getId()
    {
        return 'CF.SCANDUMP';
    }
}
