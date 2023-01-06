<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.nummultby/
 *
 * Multiply the number value stored at path by number
 */
class JSONNUMMULTBY extends RedisCommand
{
    public function getId()
    {
        return 'JSON.NUMMULTBY';
    }
}
