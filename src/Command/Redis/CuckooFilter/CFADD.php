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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cf.add/
 *
 * Adds an item to the cuckoo filter, creating the filter if it does not exist.
 */
class CFADD extends RedisCommand
{
    public function getId()
    {
        return 'CF.ADD';
    }
}
