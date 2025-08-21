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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.info/
 *
 * Returns information and statistics about a t-digest sketch.
 */
class TDIGESTINFO extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.INFO';
    }

    public function parseResponse($data)
    {
        $result = [];

        for ($i = 0, $iMax = count($data); $i < $iMax; ++$i) {
            if (array_key_exists($i + 1, $data)) {
                $result[(string) $data[$i]] = $data[++$i];
            }
        }

        return $result;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
