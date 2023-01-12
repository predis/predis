<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.debug/
 *
 * This is a container command for debugging related tasks.
 */
class JSONDEBUG extends RedisCommand
{
    public function getId()
    {
        return 'JSON.DEBUG';
    }
}
