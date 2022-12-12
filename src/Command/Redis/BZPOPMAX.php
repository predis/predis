<?php

namespace Predis\Command\Redis;

/**
 * @link https://redis.io/commands/bzpopmax/
 *
 * BZPOPMAX is the blocking variant of the sorted set ZPOPMAX primitive.
 *
 * It is the blocking version because it blocks the connection when there are
 * no members to pop from any of the given sorted sets.
 * A member with the highest score is popped from first sorted set that is non-empty,
 * with the given keys being checked in the order that they are given.
 */
class BZPOPMAX extends BZPOPMIN
{
    public function getId()
    {
        return 'BZPOPMAX';
    }
}
