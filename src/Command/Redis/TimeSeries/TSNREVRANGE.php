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

/**
 * @see https://redis.io/commands/ts.nrevrange/
 *
 * Query an explicit list of time series keys over a timestamp range in reverse
 * direction and return a timestamp-major response: [timestamp, [value_for_key_0,
 * value_for_key_1, ...]]. Rows are ordered by decreasing timestamp, the value
 * array preserves the input key order and missing values are represented as NaN.
 */
class TSNREVRANGE extends TSNRANGE
{
    public function getId()
    {
        return 'TS.NREVRANGE';
    }
}
