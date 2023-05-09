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
 * @see https://redis.io/commands/tdigest.max/
 *
 * Returns the maximum observation value from a t-digest sketch.
 */
class TDIGESTMAX extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.MAX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_string($data) || !is_float($data)) {
            return $data;
        }

        // convert Relay (RESP3) constants to strings
        if (is_nan($data)) {
            return 'nan';
        }

        switch ($data) {
            case INF: return 'inf';
            case -INF: return '-inf';
            default: return $data;
        }
    }
}
