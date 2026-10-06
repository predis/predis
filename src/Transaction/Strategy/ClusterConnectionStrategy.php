<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction\Strategy;

use Predis\Command\CommandInterface;
use Predis\Command\Redis\DISCARD;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\UNWATCH;
use Predis\Command\Redis\WATCH;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\Error;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Transaction\Exception\TransactionException;
use Predis\Transaction\MultiExecState;
use Predis\Transaction\Response\BypassTransactionResponse;
use Relay\Relay;
use SplQueue;
use Throwable;

class ClusterConnectionStrategy implements StrategyInterface
{
    /**
     * @var ClusterInterface
     */
    private $connection;

    /**
     * Server-matching slot of the current transaction.
     *
     * @var ?int
     */
    private $slot;

    /**
     * In cluster environment it needs to be queued to ensure
     * that all commands will point to the same node.
     *
     * @var SplQueue
     */
    private $commandsQueue;

    /**
     * Shows if transaction context was initialized.
     *
     * @var bool
     */
    private $isInitialized = false;

    /**
     * Physical connection holding the transaction and its WATCH state.
     *
     * @var NodeConnectionInterface|null
     */
    private $nodeConnection;

    /**
     * @var \Predis\Cluster\StrategyInterface
     */
    private $clusterStrategy;

    /**
     * @var MultiExecState
     */
    private $state;

    public function __construct(ClusterInterface $connection, MultiExecState $state)
    {
        $this->commandsQueue = new SplQueue();
        $this->connection = $connection;
        $this->state = $state;
        $this->clusterStrategy = $this->connection->getClusterStrategy();
    }

    /**
     * {@inheritDoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        if (!$this->isInitialized && !$this->state->isCAS()) {
            throw new TransactionException('Transaction context should be initialized first');
        }

        $commandSlot = $this->clusterStrategy->getSlot($command);

        if (null === $this->slot) {
            $this->slot = $commandSlot;
        }

        if (null === $commandSlot && null !== $this->slot) {
            $command->setSlot($this->slot);
        }

        if (is_int($commandSlot) && $commandSlot !== $this->slot) {
            return new Error(
                'To be able to execute a transaction against cluster, all commands should operate on the same hash slot'
            );
        }

        if ($this->state->isCAS()) {
            return new BypassTransactionResponse($this->executeBeforeMulti($command));
        }

        $this->commandsQueue->enqueue($command);

        return new Status('QUEUED');
    }

    /**
     * {@inheritDoc}
     */
    public function initializeTransaction(): bool
    {
        $this->isInitialized = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function executeTransaction()
    {
        if (!$this->isInitialized) {
            throw new TransactionException('Transaction context should be initialized first');
        }

        $exec = new EXEC();
        $multiResp = $this->setSlotAndExecute(new MULTI());

        // Begin transaction
        if (('OK' != $multiResp) && !$multiResp instanceof Relay) {
            $this->abort(new UNWATCH(), $multiResp);

            return null;
        }

        // Transaction body
        while (!$this->commandsQueue->isEmpty()) {
            /** @var CommandInterface $command */
            $command = $this->commandsQueue->dequeue();
            $commandResp = $this->setSlotAndExecute($command);

            if (('QUEUED' != $commandResp) && !$commandResp instanceof Relay) {
                $this->abort(new DISCARD(), $commandResp);

                return null;
            }
        }

        // Execute transaction
        $exec = $this->setSlotAndExecute($exec);

        if ($exec instanceof ErrorInterface) {
            $this->abort(new UNWATCH(), $exec);

            return null;
        }

        $this->reset();

        return $exec;
    }

    /**
     * Commands are queued client-side until the transaction is executed, so
     * MULTI is sent by executeTransaction() and never stays open in between.
     *
     * {@inheritDoc}
     */
    public function multi()
    {
        $this->isInitialized = true;

        return new Status('OK');
    }

    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        if (!$this->clusterStrategy->checkSameSlotForKeys($keys)) {
            throw new TransactionException('WATCHed keys should point to the same hash slot');
        }

        $this->slot = $this->clusterStrategy->getSlotByKey($keys[0]);

        $watch = new WATCH();
        $watch->setArguments($keys);

        return 'OK' == $this->executeBeforeMulti($watch);
    }

    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        // MULTI is only open while executeTransaction() is running,
        // so WATCH is all that can be pending on the node here.
        return $this->unwatch();
    }

    /**
     * {@inheritDoc}
     */
    public function unwatch()
    {
        return $this->releaseNode(new UNWATCH());
    }

    /**
     * Executes a command ahead of MULTI, releasing the node on error responses.
     *
     * @param  CommandInterface $command
     * @return mixed
     * @throws ServerException
     */
    private function executeBeforeMulti(CommandInterface $command)
    {
        $response = $this->setSlotAndExecute($command);

        if ($response instanceof ErrorInterface) {
            $this->abort(new UNWATCH(), $response);

            throw new ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Releases the node after a response that aborts the transaction. A -MOVED
     * response also updates the slots map, so the next attempt is sent to the
     * node the slot was moved to.
     *
     * @param CommandInterface $cleanup
     * @param mixed            $response
     */
    private function abort(CommandInterface $cleanup, $response): void
    {
        $node = $this->nodeConnection;
        $this->releaseNode($cleanup);

        if (!$response instanceof ErrorInterface || !$this->connection instanceof RedisCluster) {
            return;
        }

        $details = explode(' ', $response->getMessage(), 2);

        if ('MOVED' === $details[0] && isset($details[1])) {
            $this->connection->applyMovedResponse($details[1]);
        } elseif ('READONLY' === $details[0] && $node) {
            $this->connection->applyReadOnlyResponse($node);
        }
    }

    /**
     * Cleans up the node holding the transaction and resets the strategy,
     * closing the connection when the node rejects the cleanup command.
     *
     * @param  CommandInterface $command
     * @return mixed
     */
    private function releaseNode(CommandInterface $command)
    {
        try {
            if (!$this->nodeConnection) {
                return new Status('OK');
            }

            $response = $this->setSlotAndExecute($command);

            if ('OK' != $response && !$response instanceof Relay) {
                $this->nodeConnection->disconnect();
            }

            return $response;
        } finally {
            $this->reset();
        }
    }

    /**
     * Assigns slot to a command and executes.
     *
     * @param  CommandInterface $command
     * @return mixed
     */
    private function setSlotAndExecute(CommandInterface $command)
    {
        try {
            if (null !== $this->slot) {
                $command->setSlot($this->slot);
            }

            if (!$this->nodeConnection) {
                $response = $this->connection->executeCommand($command);
                $this->nodeConnection = $this->connection->getConnectionByCommand($command);

                return $response;
            }

            return $this->nodeConnection->executeCommand($command);
        } catch (Throwable $exception) {
            if ($this->nodeConnection) {
                $this->nodeConnection->disconnect();
            }
            $this->reset();

            throw $exception;
        }
    }

    private function reset(): void
    {
        $this->slot = null;
        $this->nodeConnection = null;
        $this->commandsQueue = new SplQueue();
        $this->isInitialized = false;
    }
}
