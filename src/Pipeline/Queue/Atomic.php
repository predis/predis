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
use Throwable;
use Traversable;
use NoRewindIterator;
use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\ConnectionInterface;
use Predis\Response\ErrorInterface;

/**
 * Atomic command queue that wraps flushes into MULTI / EXEC transactions.
 */
class Atomic implements CommandQueueInterface
{
    private $discarded = false;
    /** @var CommandQueueInterface */
    private $innerQueue;
    /** @var SplQueue */
    private $txCommands;

    /**
     * @inheritdoc
     */
    public function __construct(CommandQueueInterface $queue)
    {
        $this->checkInnerQueue($queue);

        $this->innerQueue = $queue;
        $this->txCommands = new SplQueue;
    }

    /**
     * Verifies if the inner command queue is compatibile with atomic queues.
     *
     * @throws InvalidArgumentException
     */
    protected function checkInnerQueue(CommandQueueInterface $queue): void
    {
        if ($queue instanceof static) {
            throw new InvalidArgumentException('Atomic command queues cannot be nested');
        }
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->txCommands);
    }

    /**
     * @inheritdoc
     */
    public function reset(): void
    {
        $this->txCommands = new SplQueue();
        $this->innerQueue->reset();
    }

    /**
     * @inheritdoc
     */
    public function enqueue(CommandInterface $command): void
    {
        if ($this->txCommands->isEmpty()) {
            $this->innerQueue->enqueue(RawCommand::create('MULTI'));
        }

        if ($this->discarded) {
            return;
        }

        if (0 === strcasecmp($command->getId(), 'DISCARD')) {
            $this->discarded = true;
            $this->reset();

            return;
        }

        $this->txCommands->enqueue($command);
        $this->innerQueue->enqueue($command);
    }

    /**
     * @inheritdoc
     */
    public function flush(ConnectionInterface $connection): Traversable
    {
        if ($this->txCommands->isEmpty()) {
            yield from [];

            return 0;
        }

        $this->innerQueue->enqueue(RawCommand::create('EXEC'));

        $pending = count($this->txCommands);
        $responses = new NoRewindIterator($this->innerQueue->flush($connection));

        if (!$responses->valid()) {
            yield from [];

            return 0;
        }

        // NOTE: first queued command is `MULTI` so we check its response as it
        // could return "-ERR" if Redis detects a nested `MULTI` when the atomic
        // queue is flushed over a connection in a running MULTI / EXEC context.
        if ($responses->current() instanceof ErrorInterface) {
            throw $this->onLogicError($connection, $responses->current()->getMessage());
        }

        for ($i = 0; $i <= $pending; $i++) {
            $responses->next();
        }

        /** @var iterable|ErrorInterface */
        $execResponse = $responses->current();

        $this->ensureValidExecResponse($connection, $execResponse);

        foreach ($execResponse as $response) {
            yield $this->txCommands->dequeue() => $response;
        }

        return count($execResponse);
    }

    /**
     * Makes sure the response payload returned by `EXEC` is valid.
     *
     * @param ConnectionInterface     $connection   Target connection
     * @param iterable|ErrorInterface $execResponse Response returned by `EXEC`
     *
     * @throws AtomicFlushException when the response returned by `EXEC` is not valid
     */
    protected function ensureValidExecResponse(ConnectionInterface $connection, $execResponse): void
    {
        if (null === $execResponse) {
            $this->reset();

            throw $this->onAbortedError($connection, 'Transaction discarded because of previous errors (NULL response)');
        } elseif ($execResponse instanceof ErrorInterface) {
            $this->reset();

            if ('EXECABORT' === $execResponse->getErrorType()) {
                throw $this->onAbortedError($connection, 'Transaction discarded because of previous errors (-EXECABORT response)');
            } else {
                throw $this->onLogicError($connection, $execResponse->getMessage());
            }
        } elseif (!is_iterable($execResponse)) {
            $this->reset();
            $connection->disconnect();

            throw $this->onStateError($connection, sprintf(
                'Protocol desynchronization detected on `EXEC` response (array expected, `%s` received)',
                is_object($execResponse) ? get_class($execResponse) : gettype($execResponse)
            ));
        }
    }

    /**
     * Returns exception for atomic state errors on queue flush.
     *
     * @param ConnectionInterface $connection Connection associated to the exception
     * @param string              $message    Exception message
     *
     * @return Throwable
     */
    protected function onStateError(ConnectionInterface $connection, string $message): Throwable
    {
        return new AtomicFlushException($connection, $message, AtomicFlushException::TX_STATE);
    }

    /**
     * Returns exception for atomic logic errors on queue flush.
     *
     * @param ConnectionInterface $connection Connection associated to the exception
     * @param string              $message    Exception message
     *
     * @return Throwable
     */
    protected function onLogicError(ConnectionInterface $connection, string $message): Throwable
    {
        return new AtomicFlushException($connection, $message, AtomicFlushException::TX_LOGIC);
    }

    /**
     * Returns exception for aborted atomic on queue flush.
     *
     * @param ConnectionInterface $connection Connection associated to the exception
     * @param string              $message    Exception message
     *
     * @return Throwable
     */
    protected function onAbortedError(ConnectionInterface $connection, string $message): Throwable
    {
        return new AtomicFlushException($connection, $message, AtomicFlushException::TX_ABORT);
    }
}
