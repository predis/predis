<?php

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.add/
 *
 * Creates an empty Bloom Filter with a single sub-filter for the
 * initial capacity requested and with an upper bound error_rate.
 */
class BFADD extends RedisCommand
{
    public function getId()
    {
        return 'BF.ADD';
    }
}
