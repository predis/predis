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
 * @see https://redis.io/commands/ts.nrange/
 *
 * Query an explicit list of time series keys over a timestamp range in forward
 * direction and return a timestamp-major response: [timestamp, [value_for_key_0,
 * value_for_key_1, ...]]. The value array preserves the input key order and
 * missing values are represented as NaN.
 */
class TSNRANGE extends RedisCommand
{
    public function getId()
    {
        return 'TS.NRANGE';
    }

    public function setArguments(array $arguments)
    {
        [$keys, $fromTimestamp, $toTimestamp] = $arguments;
        $commandArguments = (!empty($arguments[3])) ? $arguments[3]->toArray() : [];

        parent::setArguments(array_merge(
            [count($keys)],
            $keys,
            [$fromTimestamp, $toTimestamp],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();

        $keysCount = $arguments[0];
        $keys = array_slice($arguments, 1, $keysCount);
        $prefixedKeys = array_map(static function ($key) use ($prefix) {
            return $prefix . $key;
        }, $keys);

        $this->setRawArguments(array_merge(
            [$arguments[0]],
            $prefixedKeys,
            array_slice($arguments, $keysCount + 1)
        ));
    }
}
