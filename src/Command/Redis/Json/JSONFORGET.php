<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.forget/
 *
 * @see https://redis.io/commands/json.del/
 */
class JSONFORGET extends RedisCommand
{
    public function getId()
    {
        return 'JSON.FORGET';
    }
}
