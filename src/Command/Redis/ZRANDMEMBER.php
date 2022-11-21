<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

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
 * @version >= 6.2.0
 */
class ZRANDMEMBER extends RedisCommand
{

    public function getId()
    {
        return 'ZRANDMEMBER';
    }

    public function setArguments(array $arguments)
    {
        $withScores = (count($arguments) === 3)
            ? array_pop($arguments)
            : false;

        if (is_bool($withScores) && $withScores) {
            $arguments[] = 'WITHSCORES';
        }

        parent::setArguments($arguments);
    }
}
