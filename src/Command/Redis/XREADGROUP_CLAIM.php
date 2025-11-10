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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * This is a transitional command. In the next major version this command will replace XREADGROUP.
 */
class XREADGROUP_CLAIM extends RedisCommand
{
    public function getId()
    {
        return 'XREADGROUP';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = ['GROUP', $arguments[0], $arguments[1]];

        if (count($arguments) >= 4 && null !== $arguments[3]) {
            array_push($processedArguments, 'COUNT', $arguments[3]);
        }

        if (count($arguments) >= 5 && null !== $arguments[4]) {
            array_push($processedArguments, 'BLOCK', $arguments[4]);
        }

        if (count($arguments) >= 6 && false !== $arguments[5]) {
            $processedArguments[] = 'NOACK';
        }

        if (count($arguments) >= 7 && false !== $arguments[6]) {
            array_push($processedArguments, 'CLAIM', $arguments[6]);
        }

        array_push($processedArguments, 'STREAMS', ...array_keys($arguments[2]), ...array_values($arguments[2]));

        parent::setArguments($processedArguments);
    }

    public function parseResponse($data)
    {
        if (!is_array($data) || $data === array_values($data)) {
            return $data;
        }

        // Relay
        $result = [];
        foreach ($data as $key => $value) {
            $group = [$key, $value];
            $result[] = $group;
        }

        return $result;
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
