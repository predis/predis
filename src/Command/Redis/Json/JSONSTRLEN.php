<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.strlen/
 *
 * Report the length of the JSON String at path in key
 */
class JSONSTRLEN extends RedisCommand
{
    public function getId()
    {
        return 'JSON.STRLEN';
    }
}
