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
 * @see http://redis.io/commands/expireat
 *
 * EXPIREAT has the same effect and semantic as EXPIRE, but instead of specifying
 * the number of seconds representing the TTL (time to live), it takes an absolute Unix timestamp
 */
class EXPIREAT extends RedisCommand
{
    use ExpireOptions;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXPIREAT';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
