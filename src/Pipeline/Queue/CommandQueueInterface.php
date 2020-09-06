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

use Countable;
use Traversable;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;

/**
 * Defines the minimum API interface for a command queue.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface CommandQueueInterface extends Countable
{
    /**
     * Returns the number of commands in the queue.
     *
     * @return integer
     */
    public function count(): int;

    /**
     * Discards queued commands and resets the state of the queue.
     *
     * @return void
     */
    public function reset(): void;

    /**
     * Puts a command in the queue.
     *
     * @param CommandInterface $command Command to be queued
     *
     * @throws EnqueueException when an error occurs while trying to enqueue a command
     *
     * @return void
     */
    public function enqueue(CommandInterface $command): void;

    /**
     * Flushes queued commands to the connection and returns their responses.
     *
     * This method returns a Traversable that MUST be consumed by the caller to
     * make sure that pending responses on the connection are properly dequeued
     * and prevent any protocol desynchronization issue.
     *
     * @param ConnectionInterface $connection Target connection
     *
     * @throws FlushException when an error occurs while trying to flush the queue
     *
     * @return Traversable
     */
    public function flush(ConnectionInterface $connection): Traversable;
}
