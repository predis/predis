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
        $result = [];

        for ($i = 0, $iMax = count($data); $i < $iMax; $i++) {
            if (is_array($data[$i])) {
                $result[$i] = $this->parseResponse($data[$i]);
            }

            if (array_key_exists($i + 1, $data)) {
                if (is_array($data[$i + 1])) {
                    $result[$data[$i]] = $this->parseResponse($data[++$i]);
                } else {
                    $result[$data[$i]] = $data[++$i];
                }
            }
        }

        return $result;
    }
}
