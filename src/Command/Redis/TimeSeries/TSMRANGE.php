<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ts.mrange/
 *
 * Query a range across multiple time series by filters in forward direction.
 */
class TSMRANGE extends RedisCommand
{
    public function getId()
    {
        return 'TS.MRANGE';
    }

    public function setArguments(array $arguments)
    {
        [$fromTimestamp, $toTimestamp] = $arguments;
        $commandArguments = $arguments[2]->toArray();

        parent::setArguments(array_merge(
            [$fromTimestamp, $toTimestamp],
            $commandArguments
        ));
    }
}
