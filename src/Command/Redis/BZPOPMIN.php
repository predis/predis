<?php

namespace Predis\Command\Redis;

use Predis\Command\Traits\Keys;
use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bzpopmin/
 *
 * BZPOPMIN is the blocking variant of the sorted set ZPOPMIN primitive.
 *
 * It is the blocking version because it blocks the connection when there are
 * no members to pop from any of the given sorted sets.
 * A member with the lowest score is popped from first sorted set that is non-empty,
 * with the given keys being checked in the order that they are given.
 */
class BZPOPMIN extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }

    protected static $keysArgumentPositionOffset = 0;

    public function getId()
    {
        return 'BZPOPMIN';
    }

    public function setArguments(array $arguments)
    {
        $this->setKeys($arguments, false);
    }

    public function parseResponse($data)
    {
        $key = array_shift($data);

        if (null === $key) {
            return [$key];
        }

        return array_combine([$key], [[$data[0] => $data[1]]]);
    }
}
