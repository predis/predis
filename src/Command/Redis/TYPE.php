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
 * @see http://redis.io/commands/type
 */
class TYPE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'TYPE';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_string($data)) {
            return $data;
        }

        // Relay types
        switch ($data) {
            case 0: return 'none';
            case 1: return 'string';
            case 2: return 'set';
            case 3: return 'list';
            case 4: return 'zset';
            case 5: return 'hash';
            case 6: return 'stream';
            default: return $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
