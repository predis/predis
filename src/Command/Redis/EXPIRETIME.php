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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/expiretime/
 *
 * Returns the absolute Unix timestamp (since January 1, 1970)
 * in seconds at which the given key will expire.
 */
class EXPIRETIME extends RedisCommand
{
    public function getId()
    {
        return 'EXPIRETIME';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
