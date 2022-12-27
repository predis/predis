<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/smismember/
 *
 * Returns whether each member is a member of the set stored at key.
 */
class SMISMEMBER extends RedisCommand
{
    public function getId()
    {
        return 'SMISMEMBER';
    }
}
