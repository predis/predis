<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;

/**
 * @see https://redis.io/commands/zdiffstore/
 *
 * Computes the difference between the first and all successive input sorted sets
 * and stores the result in destination. The total number of input keys is specified by numkeys.
 *
 * Keys that do not exist are considered to be empty sets.
 *
 * If destination already exists, it is overwritten.
 */
class ZDIFFSTORE extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }

    public static $keysArgumentPositionOffset = 1;

    public function getId()
    {
        return 'ZDIFFSTORE';
    }
}
