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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class XREAD extends RedisCommand
{
    public function getId()
    {
        return 'XREAD';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [];

        if (array_key_exists(0, $arguments) && null !== $arguments[0]) {
            array_push($processedArguments, 'COUNT', $arguments[0]);
        }

        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            array_push($processedArguments, 'BLOCK', $arguments[1]);
        }

        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            $processedArguments[] = 'STREAMS';
            $processedArguments = array_merge($processedArguments, $arguments[2]);
        }

        $ids = array_slice($arguments, 3);
        $processedArguments = array_merge($processedArguments, $ids);

        parent::setArguments($processedArguments);
    }

    public function parseResponse($data)
    {
        if (!$data) {
            return [];
        }

        if ($data !== array_values($data)) {
            return $data; // Relay
        }

        $processedData = [];

        foreach ($data as $stream) {
            $processedData[$stream[0]] = $stream[1];
        }

        return $processedData;
    }
}
