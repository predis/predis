<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ts.del/
 *
 * Delete all samples between two timestamps for a given time series.
 */
class TSDEL extends RedisCommand
{
    public function getId()
    {
        return 'TS.DEL';
    }
}
