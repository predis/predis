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
}
