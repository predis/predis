<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/zmscore/
 *
 * Returns the scores associated with the specified members
 * in the sorted set stored at key.
 *
 * For every member that does not exist in the sorted set, a null value is returned.
 *
 */
class ZMSCORE extends RedisCommand
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'ZMSCORE';
    }
}
