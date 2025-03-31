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

/**
 * @see https://redis.io/commands/?name=function
 *
 * Container command corresponds to any FUNCTION *.
 * Represents any FUNCTION command with subcommand as first argument.
 */
class FUNCTIONS extends RedisCommand
{
    public function getId()
    {
        return 'FUNCTION';
    }

    public function setArguments(array $arguments)
    {
        switch ($arguments[0]) {
            case 'FLUSH':
                $this->setFlushArguments($arguments);
                break;

            case 'LIST':
                $this->setListArguments($arguments);
                break;

            case 'LOAD':
                $this->setLoadArguments($arguments);
                break;

            case 'RESTORE':
                $this->setRestoreArguments($arguments);
                break;

            default:
                parent::setArguments($arguments);
        }

        $this->filterArguments();
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setFlushArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0]];

        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            $processedArguments[] = strtoupper($arguments[1]);
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setListArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0]];

        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            array_push($processedArguments, 'LIBRARYNAME', $arguments[1]);
        }

        if (array_key_exists(2, $arguments) && true === $arguments[2]) {
            $processedArguments[] = 'WITHCODE';
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setLoadArguments(array $arguments): void
    {
        if (count($arguments) <= 2) {
            parent::setArguments($arguments);

            return;
        }

        $processedArguments = [$arguments[0]];
        $replace = array_pop($arguments);

        if (is_bool($replace) && $replace) {
            $processedArguments[] = 'REPLACE';
        } elseif (!is_bool($replace)) {
            $processedArguments[] = $replace;
        }

        $processedArguments[] = $arguments[1];

        parent::setArguments($processedArguments);
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setRestoreArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1]];

        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            $processedArguments[] = strtoupper($arguments[2]);
        }

        parent::setArguments($processedArguments);
    }
}
