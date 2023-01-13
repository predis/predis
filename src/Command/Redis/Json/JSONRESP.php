<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.resp/
 *
 * Return the JSON in key in Redis serialization protocol specification form
 */
class JSONRESP extends RedisCommand
{
    public function getId()
    {
        return 'JSON.RESP';
    }
}
