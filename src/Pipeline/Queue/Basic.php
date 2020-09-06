<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline\Queue;

use Traversable;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;

/**
 * Standard command queue used by default for pipelining.
 */
class Basic extends CommandQueue
{
    /**
     * Writes a command to the target connection.
     *
     * @param ConnectionInterface $connection Target connection
     * @param CommandInterface    $command    Command to be written on the target connection
     *
     * @return void
     */
    public function writeQueuedCommand(ConnectionInterface $connection, CommandInterface $command): void
    {
        $connection->writeRequest($command);
    }

    /**
     * Reads a response for a command from the target connection.
     *
     * @param ConnectionInterface $connection Target connection
     * @param CommandInterface    $command    Command associated to the pending response in the target connection
     *
     * @return mixed
     */
    public function readQueuedResponse(ConnectionInterface $connection, CommandInterface $command)
    {
        return $connection->readResponse($command);
    }

    /**
     * Writes queued commands to the target connection.
     *
     * @param ConnectionInterface $connection Target connection
     *
     * @return void
     */
    protected function writeQueuedCommands(ConnectionInterface $connection): void
    {
        foreach ($this->getQueue() as $command) {
            $this->writeQueuedCommand($connection, $command);
        }
    }

    /**
     * Reads pending responses from the target connection.
     *
     * @param ConnectionInterface $connection Target connections
     *
     * @return Traversable
     */
    protected function readQueuedResponses(ConnectionInterface $connection): Traversable
    {
        $commands = $this->getQueue();
        $dequeued = count($commands);

        while (!$commands->isEmpty()) {
            $command = $commands->dequeue();
            $response = $this->readQueuedResponse($connection, $command);

            yield $command => $response;
        }

        return $dequeued;
    }

    /**
     * @inheritdoc
     */
    public function flush(ConnectionInterface $connection): Traversable
    {
        $this->writeQueuedCommands($connection);
        $dequeued = $this->readQueuedResponses($connection);

        return $dequeued;
    }
}
