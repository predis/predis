<?php

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Command as RedisCommand;

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
}
