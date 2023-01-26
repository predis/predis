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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\Expansion;

/**
 * @see https://redis.io/commands/bf.reserve/
 *
 * Creates an empty Bloom Filter with a single sub-filter for the initial capacity
 * requested and with an upper bound error_rate.
 *
 * By default, the filter auto-scales by creating additional sub-filters when capacity is reached.
 * The new sub-filter is created with size of the previous sub-filter multiplied by expansion.
 */
class BFRESERVE extends RedisCommand
{
    use Expansion {
        Expansion::setArguments as setExpansion;
    }

    protected static $expansionArgumentPositionOffset = 3;

    public function getId()
    {
        return 'BF.RESERVE';
    }

    public function setArguments(array $arguments)
    {
        if (array_key_exists(4, $arguments) && $arguments[4]) {
            $arguments[4] = 'NONSCALING';
        }

        $this->setExpansion($arguments);
        $this->filterArguments();
    }
}
