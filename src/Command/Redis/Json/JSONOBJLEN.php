<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.objlen/
 *
 * Report the number of keys in the JSON object at path in key
 */
class JSONOBJLEN extends RedisCommand
{
    public function getId()
    {
        return 'JSON.OBJLEN';
    }
}
