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
 * Base command factory class.
 *
 * This class provides all of the common functionalities required for a command
 * factory to create new instances of Redis commands objects. It also allows to
 * define or undefine command handler classes for each command ID.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class Factory implements FactoryInterface
{
    protected $commands = [];
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function supports(string ...$commandIDs): bool
    {
        foreach ($commandIDs as $commandID) {
            if ($this->getCommandClass($commandID) === null) {
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
     * @return ?string
     */
    public function getCommandClass(string $commandID): ?string
    {
        if (isset($this->commands[$commandID = strtoupper($commandID)])) {
            return $this->commands[$commandID];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $commandID, array $arguments = []): CommandInterface
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
    public function define(string $commandID, string $commandClass): void
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
    public function undefine(string $commandID): void
    {
        unset($this->commands[strtoupper($commandID)]);
    }

    /**
     * Sets a command processor for processing command arguments.
     *
     * Command processors are used to process and transform arguments of Redis
     * commands before their newly created instances are returned to the caller
     * of "create()".
     *
     * A NULL value can be used to effectively unset any processor if previously
     * set for the command factory.
     *
     * @param ProcessorInterface|null $processor Command processor or NULL value.
     */
    public function setProcessor(?ProcessorInterface $processor): void
    {
        $this->processor = $processor;
    }

    /**
     * Returns the current command processor.
     *
     * @return ?ProcessorInterface
     */
    public function getProcessor(): ?ProcessorInterface
    {
        return $this->processor;
    }
}
