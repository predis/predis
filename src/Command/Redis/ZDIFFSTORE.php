<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;

/**
 * @link https://redis.io/commands/zdiffstore/
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
