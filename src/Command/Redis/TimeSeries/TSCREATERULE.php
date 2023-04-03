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
 * @see https://redis.io/commands/ts.createrule/
 *
 * Create a compaction rule
 */
class TSCREATERULE extends RedisCommand
{
    public function getId()
    {
        return 'TS.CREATERULE';
    }

    public function setArguments(array $arguments)
    {
        [$sourceKey, $destKey, $aggregator, $bucketDuration] = $arguments;
        $processedArguments = [$sourceKey, $destKey, 'AGGREGATION', $aggregator, $bucketDuration];

        if (count($arguments) === 5) {
            $processedArguments[] = $arguments[4];
        }

        parent::setArguments($processedArguments);
    }
}
