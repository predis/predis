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
     * Returns the FQCN of a class that represents the specified command ID.
     *
     * @codeCoverageIgnore
     *
     * @param string $commandID Command ID
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

            throw new ClientException("Command `$commandID` is not a registered Redis command.");
        }

        $command = new $commandClass();
        $command->setArguments($arguments);

        if (isset($this->processor)) {
            $this->processor->process($command);
        }

        return $command;
    }

    /**
     * Defines a command in the factory.
     *
     * Only classes implementing Predis\Command\CommandInterface are allowed to
     * handle a command. If the command specified by its ID is already handled
     * by the factory, the underlying command class is replaced by the new one.
     *
     * @param string $commandID    Command ID
     * @param string $commandClass FQCN of a class implementing Predis\Command\CommandInterface
     *
     * @throws \InvalidArgumentException
     */
    public function defineCommand($commandID, $commandClass)
    {
        if (!is_a($commandClass, 'Predis\Command\CommandInterface', true)) {
            throw new \InvalidArgumentException(
                "Class $commandClass must implement Predis\Command\CommandInterface"
            );
        }

        $this->commands[strtoupper($commandID)] = $commandClass;
    }

    /**
     * Undefines a command in the factory.
     *
     * When the factory already has a class handler associated to the specified
     * command ID it is removed from the map of known commands. Nothing happens
     * when the command is not handled by the factory.
     *
     * @param string $commandID Command ID
     */
    public function undefineCommand($commandID)
    {
        unset($this->commands[strtoupper($commandID)]);
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
