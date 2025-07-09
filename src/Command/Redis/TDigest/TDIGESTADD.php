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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.add/
 *
 * Adds one or more observations to a t-digest sketch.
 */
class TDIGESTADD extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.ADD';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
