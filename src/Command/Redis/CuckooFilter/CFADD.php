<?php

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/cf.add/
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
