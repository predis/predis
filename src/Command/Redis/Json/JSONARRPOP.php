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

namespace Predis\Command\Redis\Json;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/json.arrpop/
 *
 * Remove and return an element from the index in the array
 */
class JSONARRPOP extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRPOP';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
