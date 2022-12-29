<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\With\WithScores;

/**
 * @link https://redis.io/commands/zrandmember/
 *
 * Return a random element from the sorted set value stored at key.
 *
 * If the provided count argument is positive, return an array of distinct elements.
 *
 * If called with a negative count, the behavior changes and the command
 * is allowed to return the same element multiple times.
 *
 */
class ZRANDMEMBER extends RedisCommand
{
    use WithScores;

    public function getId()
    {
        return 'ZRANDMEMBER';
    }
}
