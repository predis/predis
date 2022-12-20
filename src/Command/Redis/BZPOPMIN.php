<?php

namespace Predis\Command\Redis;

use Predis\Command\Redis\AbstractCommand\BZPOPBase;

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
class BZPOPMIN extends BZPOPBase
{
    public function getId(): string
    {
        return 'BZPOPMIN';
    }
}
