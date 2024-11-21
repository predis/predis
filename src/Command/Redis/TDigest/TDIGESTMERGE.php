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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.merge/
 *
 * Merges multiple t-digest sketches into a single sketch.
 */
class TDIGESTMERGE extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.MERGE';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = array_merge([$arguments[0], count($arguments[1])], $arguments[1]);

        for ($i = 2, $iMax = count($arguments); $i < $iMax; $i++) {
            if (is_int($arguments[$i]) && $arguments[$i] !== 0) {
                array_push($processedArguments, 'COMPRESSION', $arguments[$i]);
            } elseif (is_bool($arguments[$i]) && $arguments[$i]) {
                $processedArguments[] = 'OVERRIDE';
            }
        }

        parent::setArguments($processedArguments);
    }
}
