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
 * @see https://redis.io/commands/ts.madd/
 *
 * Append new samples to one or more time series.
 */
class TSMADD extends RedisCommand
{
    public function getId()
    {
        return 'TS.MADD';
    }

    public function prefixKeys($prefix)
    {
        if ($arguments = $this->getArguments()) {
            for ($i = 0, $l = count($arguments); $i < $l; $i += 3) {
                $arguments[$i] = $prefix . $arguments[$i];
            }

            $this->setArguments($arguments);
        }
    }
}
