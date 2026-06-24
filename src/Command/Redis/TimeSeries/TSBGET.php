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
 * @see https://redis.io/commands/ts.bget/
 *
 * Read-only blocking command that retrieves batches of time series samples at
 * or after a cursor timestamp. It blocks until at least min_count qualifying
 * samples are available, until timeout expires, or until the key is removed,
 * whichever occurs first.
 */
class TSBGET extends RedisCommand
{
    public function getId()
    {
        return 'TS.BGET';
    }

    public function setArguments(array $arguments)
    {
        [$key, $timestamp, $timeout] = $arguments;
        $commandArguments = (!empty($arguments[3])) ? $arguments[3]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $timestamp, $timeout],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
