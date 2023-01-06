<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.strappend/
 *
 * Append the json-string values to the string at path
 */
class JSONSTRAPPEND extends RedisCommand
{
    public function getId()
    {
        return 'JSON.STRAPPEND';
    }
}
