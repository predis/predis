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
 * @see https://redis.io/commands/ts.alter/
 *
 * Update the retention, chunk size, duplicate policy, and labels of an existing time series.
 */
class TSALTER extends RedisCommand
{
    public function getId()
    {
        return 'TS.ALTER';
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
}
