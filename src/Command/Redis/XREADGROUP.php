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
 * @deprecated Public API will be changed in the next major version.
 * XREADGROUP_CLAIM API will be used instead.
 */
class XREADGROUP extends RedisCommand
{
    public function getId()
    {
        return 'XREADGROUP';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = ['GROUP', $arguments[0], $arguments[1]];

        if (count($arguments) >= 3 && null !== $arguments[2]) {
            array_push($processedArguments, 'COUNT', $arguments[2]);
        }

        if (count($arguments) >= 4 && null !== $arguments[3]) {
            array_push($processedArguments, 'BLOCK', $arguments[3]);
        }

        if (count($arguments) >= 5 && false !== $arguments[4]) {
            $processedArguments[] = 'NOACK';
        }

        $processedArguments[] = 'STREAMS';
        $keyOrIds = array_slice($arguments, 5);

        parent::setArguments(array_merge($processedArguments, $keyOrIds));
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
