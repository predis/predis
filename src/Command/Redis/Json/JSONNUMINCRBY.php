<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.numincrby/
 *
 * Increment the number value stored at path by number
 */
class JSONNUMINCRBY extends RedisCommand
{
    public function getId()
    {
        return 'JSON.NUMINCRBY';
    }
}
