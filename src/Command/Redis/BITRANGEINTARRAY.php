<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/bitrangeintarray/
 *
 * Returns the offsets of the set (or, with ZEROS, clear) bits in a range of a bitmap.
 */
class BITRANGEINTARRAY extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BITRANGEINTARRAY';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processed = array_slice($arguments, 0, 3);

        if (isset($arguments[3])) {
            $processed[] = strtoupper($arguments[3]);
        }

        if (isset($arguments[4])) {
            $processed[] = 'LIMIT';
            $processed[] = $arguments[4];
        }

        if (!empty($arguments[5])) {
            $processed[] = 'ZEROS';
        }

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
