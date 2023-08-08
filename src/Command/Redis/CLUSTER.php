<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/?name=cluster
 */
class CLUSTER extends RedisCommand
{
    public function getId()
    {
        return 'CLUSTER';
    }
}
