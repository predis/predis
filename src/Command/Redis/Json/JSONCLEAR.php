<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.clear/
 *
 * Clear container values (arrays/objects) and set numeric values to 0
 */
class JSONCLEAR extends RedisCommand
{
    public function getId()
    {
        return 'JSON.CLEAR';
    }
}
