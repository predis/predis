<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrpop/
 *
 * Remove and return an element from the index in the array
 */
class JSONARRPOP extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRPOP';
    }
}
