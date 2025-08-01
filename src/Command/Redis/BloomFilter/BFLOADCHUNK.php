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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/bf.loadchunk/
 *
 * Restores a filter previously saved using SCANDUMP. See the SCANDUMP command for example usage.
 */
class BFLOADCHUNK extends RedisCommand
{
    public function getId()
    {
        return 'BF.LOADCHUNK';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
