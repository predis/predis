<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/commands/shutdown
 */
class SHUTDOWN extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SHUTDOWN';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (empty($arguments)) {
            parent::setArguments($arguments);

            return;
        }

        $processedArguments = [];

        if (array_key_exists(0, $arguments) && null !== $arguments[0]) {
            $processedArguments[] = ($arguments[0]) ? 'SAVE' : 'NOSAVE';
        }

        if (array_key_exists(1, $arguments) && false !== $arguments[1]) {
            $processedArguments[] = 'NOW';
        }

        if (array_key_exists(2, $arguments) && false !== $arguments[2]) {
            $processedArguments[] = 'FORCE';
        }

        if (array_key_exists(3, $arguments) && false !== $arguments[3]) {
            $processedArguments[] = 'ABORT';
        }

        parent::setArguments($processedArguments);
    }
}
