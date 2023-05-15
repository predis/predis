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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.byrevrank/
 *
 * Returns, for each input reverse rank, an estimation of the value (floating-point) with that reverse rank.
 */
class TDIGESTBYREVRANK extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.BYREVRANK';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        // convert Relay (RESP3) constants to strings
        return array_map(function ($value) {
            if (is_string($value) || !is_float($value)) {
                return $value;
            }

            if (is_nan($value)) {
                return 'nan';
            }

            switch ($value) {
                case INF: return 'inf';
                case -INF: return '-inf';
                default: return $value;
            }
        }, $data);
    }
}
