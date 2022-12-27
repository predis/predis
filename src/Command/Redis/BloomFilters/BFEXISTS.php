<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.exists/
 *
 * Determines whether an item may exist in the Bloom Filter or not.
 */
class BFEXISTS extends RedisCommand
{
    public function getId()
    {
        return 'BF.EXISTS';
    }
}
