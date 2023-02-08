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

namespace Predis\Command\Redis\TopK;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/topk.incrby/
 *
 * Increase the score of an item in the data structure by increment.
 * Multiple items' score can be increased at once.
 * If an item enters the Top-K list, the item which is expelled is returned.
 */
class TOPKINCRBY extends RedisCommand
{
    public function getId()
    {
        return 'TOPK.INCRBY';
    }
}
