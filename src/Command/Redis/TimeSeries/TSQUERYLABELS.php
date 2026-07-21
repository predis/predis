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

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ts.querylabels/
 *
 * Returns label metadata for time series matching a filter list: the set of
 * all values assigned to the given label name (VALUES label), or the set of
 * all label names when no label is given (LABELS).
 */
class TSQUERYLABELS extends RedisCommand
{
    public const LABELS = 'LABELS';
    public const VALUES = 'VALUES';

    public function getId()
    {
        return 'TS.QUERYLABELS';
    }

    public function setArguments(array $arguments)
    {
        $label = $arguments[0] ?? null;

        $processed = (null === $label)
            ? [self::LABELS]
            : [self::VALUES, $label];

        $filterExpressions = array_slice($arguments, 1);

        if (!empty($filterExpressions)) {
            array_push($processed, 'FILTER', ...$filterExpressions);
        }

        parent::setArguments($processed);
    }
}
