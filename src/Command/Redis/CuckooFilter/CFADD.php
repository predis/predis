<?php

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/cf.add/
 *
 * Adds an item to the cuckoo filter
 */
class CFADD extends RedisCommand
{
    public function getId()
    {
        return 'CF.ADD';
    }
}
