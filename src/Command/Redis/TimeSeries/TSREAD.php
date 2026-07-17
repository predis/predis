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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/ts.read/
 *
 * Read a batch of samples with timestamps at or after a cursor, in ascending
 * timestamp order. With the optional BLOCK group the command waits until at
 * least min_count qualifying samples are available or until a timeout elapses.
 */
class TSREAD extends RedisCommand
{
    public function getId()
    {
        return 'TS.READ';
    }

    public function setArguments(array $arguments)
    {
        [$key, $timestamp] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $timestamp],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
