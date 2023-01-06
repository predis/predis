<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrappend/
 *
 * Append the json values into the array at path after the last element in it
 */
class JSONARRAPPEND extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRAPPEND';
    }
}
