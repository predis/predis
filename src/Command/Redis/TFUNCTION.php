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
 * Load a new library to RedisGears.
 *
 * In order to be used in cluster mode
 * @see https://github.com/predis/predis#redis-gears-with-cluster
 */
class TFUNCTION extends RedisCommand
{
    /**
     * @var string
     */
    private $subcommand;

    public function getId()
    {
        return 'TFUNCTION';
    }

    public function setArguments(array $arguments)
    {
        $this->subcommand = $arguments[0];

        switch ($this->subcommand) {
            case 'LOAD':
                $this->setLoadArguments($arguments);
                break;
            case 'LIST':
                $this->setListArguments($arguments);
                break;

            default:
                parent::setArguments($arguments);
        }
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setLoadArguments(array $arguments): void
    {
        $subcommand = array_shift($arguments);
        $processedArguments = [$subcommand];
        $argumentsCount = min(count($arguments), 3);

        if ($argumentsCount > 1 && true === $arguments[1]) {
            $processedArguments[] = 'REPLACE';
        }

        if ($argumentsCount > 2 && null !== $arguments[2]) {
            array_push($processedArguments, 'CONFIG', $arguments[2]);
        }

        $processedArguments[] = $arguments[0];

        parent::setArguments($processedArguments);
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setListArguments(array $arguments): void
    {
        $subcommand = array_shift($arguments);
        $processedArguments = [$subcommand];

        if (array_key_exists(0, $arguments) && true === $arguments[0]) {
            $processedArguments[] = 'WITHCODE';
        }

        if (array_key_exists(1, $arguments) && $arguments[1] > 0) {
            $verboseLevel = min($arguments[1], 3);

            for ($i = 0; $i < $verboseLevel; $i++) {
                $processedArguments[] = 'v';
            }
        }

        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            array_push($processedArguments, 'LIBRARY', $arguments[2]);
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param                    $data
     * @return array|string|null
     */
    public function parseResponse($data)
    {
        if ($this->subcommand === 'LIST') {
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

        return $data;
    }
}
