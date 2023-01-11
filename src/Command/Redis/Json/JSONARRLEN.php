<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrlen/
 *
 * Report the length of the JSON array at path in key
 */
class JSONARRLEN extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRLEN';
    }
}
