<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.type/
 *
 * Report the type of JSON value at path
 */
class JSONTYPE extends RedisCommand
{
    public function getId()
    {
        return 'JSON.TYPE';
    }
}
