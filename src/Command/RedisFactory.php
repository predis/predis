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

namespace Predis\Command;

use Predis\Command\Redis\FUNCTIONS;

/**
 * Command factory for mainline Redis servers.
 *
 * This factory is intended to handle standard commands implemented by mainline
 * Redis servers. By default it maps a command ID to a specific command handler
 * class in the Predis\Command\Redis namespace but this can be overridden for
 * any command ID simply by defining a new command handler class implementing
 * Predis\Command\CommandInterface.
 */
class RedisFactory extends Factory
{
    public function __construct()
    {
        $this->commands = [
            'ECHO' => 'Predis\Command\Redis\ECHO_',
            'EVAL' => 'Predis\Command\Redis\EVAL_',
            'OBJECT' => 'Predis\Command\Redis\OBJECT_',
            // Class name corresponds to PHP reserved word "function", added mapping to bypass restrictions
            'FUNCTION' => FUNCTIONS::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandClass(string $commandID): ?string
    {
        $commandID = strtoupper($commandID);

        if (isset($this->commands[$commandID]) || array_key_exists($commandID, $this->commands)) {
            $commandClass = $this->commands[$commandID];
        } elseif (class_exists($commandClass = "Predis\Command\Redis\\$commandID")) {
            $this->commands[$commandID] = $commandClass;
        } else {
            return null;
        }

        return $commandClass;
    }

    /**
     * {@inheritdoc}
     */
    public function undefine(string $commandID): void
    {
        // NOTE: we explicitly associate `NULL` to the command ID in the map
        // instead of the parent's `unset()` because our subclass tries to load
        // a predefined class from the Predis\Command\Redis namespace when no
        // explicit mapping is defined, see RedisFactory::getCommandClass() for
        // details of the implementation of this mechanism.
        $this->commands[strtoupper($commandID)] = null;
    }
}
