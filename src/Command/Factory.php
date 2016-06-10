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

use Predis\ClientException;
use Predis\Command\Processor\ProcessorInterface;

/**
 * Base command factory.
 *
 * This class provides all of the common functionalities needed for the creation
 * of new instances of Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class Factory implements FactoryInterface
{
    protected $commands = array();
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function supportsCommand($commandID)
    {
        return $this->getCommandClass($commandID) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCommands(array $commandIDs)
    {
        foreach ($commandIDs as $commandID) {
            if (!$this->supportsCommand($commandID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the FQN of a class that represents the specified command ID.
     *
     * @codeCoverageIgnore
     *
     * @param string $commandID Command ID.
     *
     * @return string|null
     */
    public function getCommandClass($commandID)
    {
        if (isset($this->commands[$commandID = strtoupper($commandID)])) {
            return $this->commands[$commandID];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, array $arguments = array())
    {
        if (!$commandClass = $this->getCommandClass($commandID)) {
            $commandID = strtoupper($commandID);

            throw new ClientException("Command '$commandID' is not a registered Redis command.");
        }

        $command = new $commandClass();
        $command->setArguments($arguments);

        if (isset($this->processor)) {
            $this->processor->process($command);
        }

        return $command;
    }

    /**
     * Defines a new command in the factory.
     *
     * @param string $commandID Command ID.
     * @param string $class     Fully-qualified name of a Predis\Command\CommandInterface.
     *
     * @throws \InvalidArgumentException
     */
    public function defineCommand($commandID, $class)
    {
        if ($class !== null) {
            $reflection = new \ReflectionClass($class);

            if (!$reflection->isSubclassOf('Predis\Command\CommandInterface')) {
                throw new \InvalidArgumentException("The class '$class' is not a valid command class.");
            }
        }

        $this->commands[strtoupper($commandID)] = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(ProcessorInterface $processor = null)
    {
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor()
    {
        return $this->processor;
    }
}
