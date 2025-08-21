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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/ts.decrby/
 *
 * Decrease the value of the sample with the maximum existing timestamp,
 * or create a new sample with a value equal to the value of the sample
 * with the maximum existing timestamp with a given decrement.
 */
class TSDECRBY extends RedisCommand
{
    public function getId()
    {
        return 'TS.DECRBY';
    }

    public function setArguments(array $arguments)
    {
        [$key, $value] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $value],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
