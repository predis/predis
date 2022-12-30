<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\Expansion;

/**
 * @link https://redis.io/commands/bf.reserve/
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
