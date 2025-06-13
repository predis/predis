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
use Predis\Command\Redis\Utils\CommandUtility;

/**
 * @see http://redis.io/commands/xclaim
 */
class XCLAIM extends RedisCommand
{
    public function getId(): string
    {
        return 'XCLAIM';
    }

    public function setArguments(array $arguments): void
    {
        if (count($arguments) < 5) {
            return;
        }

        $processedArguments = array_slice($arguments, 0, 4);
        $ids = $arguments[4];
        $processedArguments = array_merge($processedArguments, is_array($ids) ? $ids : [$ids]);

        if (array_key_exists(5, $arguments) && null !== $arguments[5]) {
            array_push($processedArguments, 'IDLE', $arguments[5]);
        }

        if (array_key_exists(6, $arguments) && null !== $arguments[6]) {
            array_push($processedArguments, 'TIME', $arguments[6]);
        }

        if (array_key_exists(7, $arguments) && null !== $arguments[7]) {
            array_push($processedArguments, 'RETRYCOUNT', $arguments[7]);
        }

        if (array_key_exists(8, $arguments) && false !== $arguments[8]) {
            $processedArguments[] = 'FORCE';
        }

        if (array_key_exists(9, $arguments) && false !== $arguments[9]) {
            $processedArguments[] = 'JUSTID';
        }

        if (array_key_exists(10, $arguments) && false !== $arguments[10]) {
            array_push($processedArguments, 'LASTID', $arguments[10]);
        }

        parent::setArguments($processedArguments);
    }

    public function parseResponse($data): array
    {
        // JUSTID format
        if (isset($data[0]) && !is_array($data[0])) {
            return $data;
        }

        $result = [];
        foreach ($data as [$id, $kvDict]) {
            $result[$id] = CommandUtility::arrayToDictionary($kvDict);
        }

        return $result;
    }

    public function parseResp3Response($data): array
    {
        return $this->parseResponse($data);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
