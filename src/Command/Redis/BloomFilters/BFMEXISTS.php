<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.mexists/
 *
 * Determines if one or more items may exist in the filter or not.
 */
class BFMEXISTS extends RedisCommand
{
    public function getId()
    {
        return 'BF.MEXISTS';
    }
}
