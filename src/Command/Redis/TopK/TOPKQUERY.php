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

namespace Predis\Command\Redis\TopK;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/topk.query/
 *
 * Checks whether an item is one of Top-K items.
 * Multiple items can be checked at once.
 */
class TOPKQUERY extends RedisCommand
{
    public function getId()
    {
        return 'TOPK.QUERY';
    }
}
