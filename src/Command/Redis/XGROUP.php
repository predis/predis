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
 * @see https://redis.io/commands/?name=xgroup
 *
 * Container command corresponds to any XGROUP *.
 * Represents any XGROUP command with subcommand as first argument.
 */
class XGROUP extends RedisCommand
{
    public function getId()
    {
        return 'XGROUP';
    }

    public function setArguments(array $arguments)
    {
        switch ($arguments[0]) {
            case 'CREATE':
                $this->setCreateArguments($arguments);

                return;

            case 'SETID':
                $this->setSetIdArguments($arguments);

                return;

            default:
                parent::setArguments($arguments);
        }
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setCreateArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];

        if (array_key_exists(4, $arguments) && true === $arguments[4]) {
            $processedArguments[] = 'MKSTREAM';
        }

        if (array_key_exists(5, $arguments)) {
            array_push($processedArguments, 'ENTRIESREAD', $arguments[5]);
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setSetIdArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];

        if (array_key_exists(4, $arguments)) {
            array_push($processedArguments, 'ENTRIESREAD', $arguments[4]);
        }

        parent::setArguments($processedArguments);
    }
}
