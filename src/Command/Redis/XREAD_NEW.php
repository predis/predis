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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * This is a transitional command. In the next major version this command will replace XREAD.
 */
class XREAD_NEW extends RedisCommand
{
    public function getId()
    {
        return 'XREAD';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [];

        if (count($arguments) >= 2 && null !== $arguments[1]) {
            array_push($processedArguments, 'COUNT', $arguments[1]);
        }

        if (count($arguments) >= 4 && null !== $arguments[3]) {
            array_push($processedArguments, 'MAXCOUNT', $arguments[3]);
        }

        if (count($arguments) >= 5 && null !== $arguments[4]) {
            array_push($processedArguments, 'MAXSIZE', $arguments[4]);
        }

        if (count($arguments) >= 3 && null !== $arguments[2]) {
            array_push($processedArguments, 'BLOCK', $arguments[2]);
        }

        array_push($processedArguments, 'STREAMS', ...array_keys($arguments[0]), ...array_values($arguments[0]));

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

    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();
        $keyIdsStartingIndex = array_search('STREAMS', $arguments) + 1;
        $keysAndIdsCount = count($arguments) - $keyIdsStartingIndex;
        $keysCount = $keysAndIdsCount / 2;

        for ($i = $keyIdsStartingIndex; $i < $keyIdsStartingIndex + $keysCount; $i++) {
            $arguments[$i] = $prefix . $arguments[$i];
        }

        parent::setRawArguments($arguments);
    }
}
