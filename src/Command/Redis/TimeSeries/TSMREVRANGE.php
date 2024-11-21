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

/**
 * @see https://redis.io/commands/ts.mrevrange/
 *
 * Query a range across multiple time series by filters in reverse direction.
 */
class TSMREVRANGE extends TSMRANGE
{
    public function getId()
    {
        return 'TS.MREVRANGE';
    }
}
