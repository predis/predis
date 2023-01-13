<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.toggle/
 *
 * Toggle a Boolean value stored at path
 */
class JSONTOGGLE extends RedisCommand
{
    public function getId()
    {
        return 'JSON.TOGGLE';
    }
}
