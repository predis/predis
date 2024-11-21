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

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\With\WithScores;

/**
 * @see https://redis.io/commands/zrandmember/
 *
 * Return a random element from the sorted set value stored at key.
 *
 * If the provided count argument is positive, return an array of distinct elements.
 *
 * If called with a negative count, the behavior changes and the command
 * is allowed to return the same element multiple times.
 */
class ZRANDMEMBER extends RedisCommand
{
    use WithScores;

    public function getId()
    {
        return 'ZRANDMEMBER';
    }
}
