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

use SplQueue;
use Traversable;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;

/**
 * Base class for implementing a command queue.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class CommandQueue implements CommandQueueInterface
{
    /** @var SplQueue */
    private $queue;

    /**
     * Initializes a new command queue.
     */
    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * @inheritdoc
     */
    public function reset(): void
    {
        $this->queue = new SplQueue();
    }

    /**
     * @inheritdoc
     */
    public function enqueue(CommandInterface $command): void
    {
        $this->queue->enqueue($command);
    }

    /**
     * @inheritdoc
     */
    public abstract function flush(ConnectionInterface $connection): Traversable;

    /**
     * Returns the underlying queue storage.
     *
     * @return SplQueue
     */
    protected function getQueue(): SplQueue
    {
        return $this->queue;
    }
}
