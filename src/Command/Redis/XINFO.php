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

use Predis\Command\Argument\ArrayableArgument;
use Predis\Command\Command as RedisCommand;
use Predis\Command\Redis\Utils\CommandUtility;

class XINFO extends RedisCommand
{
    public function getId()
    {
        return 'XINFO';
    }

    public function setArguments(array $arguments)
    {
        if ($arguments[0] === 'STREAM') {
            $this->setStreamArguments($arguments);
        } else {
            parent::setArguments($arguments);
        }
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setStreamArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1]];

        if (array_key_exists(2, $arguments) && $arguments[2] instanceof ArrayableArgument) {
            $processedArguments = array_merge($processedArguments, $arguments[2]->toArray());
        }

        parent::setArguments($processedArguments);
    }

    public function parseResponse($data)
    {
        if ($this->getArgument(0) === 'STREAM') {
            return $this->parseStreamResponse($data);
        }

        return $this->parseDict($data);
    }

    private function parseStreamResponse($data): array
    {
        $result = CommandUtility::arrayToDictionary($data, null, false);

        if (isset($result['entries'])) {
            $result['entries'] = $this->parseDict($result['entries']);
        }

        if (isset($result['groups']) && is_array($result['groups'])) {
            $result['groups'] = array_map(static function ($group) {
                $group = CommandUtility::arrayToDictionary($group, null, false);
                if (isset($group['consumers'])) {
                    $group['consumers'] = array_map(static function ($consumer) {
                        return CommandUtility::arrayToDictionary($consumer, null, false);
                    }, $group['consumers']);
                }

                return $group;
            }, $result['groups']);
        }

        return $result;
    }

    public function parseResp3Response($data)
    {
        $result = $data;
        if (isset($result['entries'])) {
            $result['entries'] = $this->parseDict($result['entries']);
        }

        return $result;
    }

    private function parseDict($data): array
    {
        $result = [];

        for ($i = 0, $iMax = count($data); $i < $iMax; $i++) {
            if (is_array($data[$i])) {
                $result[$i] = $this->parseDict($data[$i]);
                continue;
            }

            if (array_key_exists($i + 1, $data)) {
                if (is_array($data[$i + 1])) {
                    $result[$data[$i]] = $this->parseDict($data[++$i]);
                } else {
                    $result[$data[$i]] = $data[++$i];
                }
            }
        }

        return $result;
    }
}
