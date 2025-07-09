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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/topk.add/
 *
 * Adds an item to the data structure.
 * Multiple items can be added at once.
 * If an item enters the Top-K list, the item which is expelled is returned.
 * This allows dynamic heavy-hitter detection of items being entered or expelled from Top-K list.
 */
class TOPKADD extends RedisCommand
{
    public function getId()
    {
        return 'TOPK.ADD';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
