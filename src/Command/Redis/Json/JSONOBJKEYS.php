<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.objkeys/
 *
 * Return the keys in the object that's referenced by path
 */
class JSONOBJKEYS extends RedisCommand
{
    public function getId()
    {
        return 'JSON.OBJKEYS';
    }
}
