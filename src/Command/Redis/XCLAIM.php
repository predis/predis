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

use Predis\Command\Argument\Stream\XClaimOptions;
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
        $argumentsCount = count($arguments);
        if ($argumentsCount < 5 || $argumentsCount > 6) {
            return;
        }

        $options = $argumentsCount === 6 ? array_pop($arguments) : null;
        $ids = array_pop($arguments);
        $processedArguments = array_merge(
            $arguments,
            is_array($ids) ? $ids : [$ids],
            $options instanceof XClaimOptions ? $options->toArray() : []
        );
        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }

    public function parseResponse($data): array
    {
        if (!is_array($data) || !$data) {
            return [];
        }

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
}
