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
 * @see https://redis.io/commands/cf.count/
 *
 * Returns the number of times an item may be in the filter.
 * Because this is a probabilistic data structure, this may not necessarily be accurate.
 */
class CFCOUNT extends RedisCommand
{
    public function getId()
    {
        return 'CF.COUNT';
    }
}
