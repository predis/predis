<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.del/
 *
 * Delete a value
 */
class JSONDEL extends RedisCommand
{
    public function getId()
    {
        return 'JSON.DEL';
    }
}
