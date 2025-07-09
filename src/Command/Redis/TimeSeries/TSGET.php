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
 * @see https://redis.io/commands/ts.get/
 *
 * Get the sample with the highest timestamp from a given time series.
 */
class TSGET extends RedisCommand
{
    public function getId()
    {
        return 'TS.GET';
    }

    public function setArguments(array $arguments)
    {
        [$key] = $arguments;
        $commandArguments = (!empty($arguments[1])) ? $arguments[1]->toArray() : [];

        parent::setArguments(array_merge(
            [$key],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
