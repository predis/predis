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
use Predis\Command\Traits\Expire\ExpireOptions;

/**
 * @see http://redis.io/commands/expire
 *
 * Set a timeout on key.
 * After the timeout has expired, the key will automatically be deleted.
 * A key with an associated timeout is often said to be volatile in Redis terminology.
 */
class EXPIRE extends RedisCommand
{
    use ExpireOptions;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXPIRE';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
