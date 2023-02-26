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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/lcs/
 *
 * The LCS command implements the longest common subsequence algorithm.
 */
class LCS extends RedisCommand
{
    public function getId()
    {
        return 'LCS';
    }

    public function setArguments(array $arguments)
    {
        if (isset($arguments[2]) && $arguments[2]) {
            $arguments[2] = 'LEN';
        }

        if (isset($arguments[3]) && $arguments[3]) {
            $arguments[3] = 'IDX';
        }

        if (isset($arguments[5]) && $arguments[5]) {
            $arguments[5] = 'WITHMATCHLEN';
        }

        if (isset($arguments[4])) {
            if ($arguments[4] !== 0) {
                $argumentsBefore = array_slice($arguments, 0, 4);
                $argumentsAfter = array_slice($arguments, 5);
                $arguments = array_merge($argumentsBefore, ['MINMATCHLEN', $arguments[4]], $argumentsAfter);
            } else {
                $arguments[4] = false;
            }
        }

        parent::setArguments($arguments);
        $this->filterArguments();
    }

    public function parseResponse($data)
    {
        if (is_array($data)) {
            if ($data !== array_values($data)) {
                return $data; // Relay
            }

            return [$data[0] => $data[1], $data[2] => $data[3]];
        }

        return $data;
    }
}
