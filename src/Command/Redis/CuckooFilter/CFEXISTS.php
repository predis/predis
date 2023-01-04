<?php

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/cf.exists/
 *
 * Check if an item exists in a Cuckoo Filter key.
 */
class CFEXISTS extends RedisCommand
{
    public function getId()
    {
        return 'CF.EXISTS';
    }
}
