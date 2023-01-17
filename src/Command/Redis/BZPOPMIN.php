<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Redis\AbstractCommand\BZPOPBase;

/**
 * @see https://redis.io/commands/bzpopmin/
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
