<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Redis\AbstractCommand\BZPOPBase;

/**
 * @see https://redis.io/commands/bzpopmax/
 *
 * BZPOPMAX is the blocking variant of the sorted set ZPOPMAX primitive.
 *
 * It is the blocking version because it blocks the connection when there are
 * no members to pop from any of the given sorted sets.
 * A member with the highest score is popped from first sorted set that is non-empty,
 * with the given keys being checked in the order that they are given.
 */
class BZPOPMAX extends BZPOPBase
{
    public function getId(): string
    {
        return 'BZPOPMAX';
    }
}
