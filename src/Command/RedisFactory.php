<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * Command factory for the mainline Redis server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisFactory extends Factory
{
    /**
     *
     */
    public function __construct()
    {
        $this->commands = array(
            'ECHO' => 'Predis\Command\Redis\ECHO_',
            'EVAL' => 'Predis\Command\Redis\EVAL_',
            'OBJECT' => 'Predis\Command\Redis\OBJECT_',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandClass($commandID)
    {
        $commandID = strtoupper($commandID);

        if (isset($this->commands[$commandID]) || array_key_exists($commandID, $this->commands)) {
            $commandClass = $this->commands[$commandID];
        } elseif (class_exists($commandClass = "Predis\Command\Redis\\$commandID")) {
            $this->commands[$commandID] = $commandClass;
        } else {
            return;
        }

        return $commandClass;
    }

    /**
     * {@inheritdoc}
     */
    public function undefineCommand($commandID)
    {
        // NOTE: we explicitly associate `NULL` to the command ID in the map
        // instead of the parent's `unset()` because our subclass tries to load
        // a predefined class from the Predis\Command\Redis namespace when no
        // explicit mapping is defined, see RedisFactory::getCommandClass() for
        // details of the implementation of this mechanism.
        $this->commands[strtoupper($commandID)] = null;
    }

}
