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

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ts.range/
 *
 * Query a range in forward direction.
 */
class TSRANGE extends RedisCommand
{
    public function getId()
    {
        return 'TS.RANGE';
    }

    public function setArguments(array $arguments)
    {
        [$key, $fromTimestamp, $toTimestamp] = $arguments;
        $commandArguments = (!empty($arguments[3])) ? $arguments[3]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $fromTimestamp, $toTimestamp],
            $commandArguments
        ));
    }
}
