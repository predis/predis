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
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\Error;
use Predis\Response\ErrorInterface;
use Predis\Response\Status;
use Predis\Transaction\Exception\TransactionException;
use Predis\Transaction\MultiExecState;
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
     * @var bool
     */
    private $multiStarted = false;

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
        if (!$this->isInitialized) {
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

        $this->commandsQueue->enqueue($command);

        return new Status('QUEUED');
    }

    /**
     * {@inheritDoc}
     */
    public function initializeTransaction(): bool
    {
        if ($this->isInitialized) {
            return true;
        }

        $this->commandsQueue->enqueue(new MULTI());
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

        /** @var MULTI $multi */
        $multi = $this->commandsQueue->dequeue();
        $this->multiStarted = true;
        $multiResp = $this->setSlotAndExecute($multi);

        // Begin transaction
        if (('OK' != $multiResp) && !$multiResp instanceof Relay) {
            $this->discard();

            return null;
        }

        // Transaction body
        while (!$this->commandsQueue->isEmpty()) {
            /** @var CommandInterface $command */
            $command = $this->commandsQueue->dequeue();
            $commandResp = $this->setSlotAndExecute($command);

            if (('QUEUED' != $commandResp) && !$commandResp instanceof Relay) {
                $this->discard();

                return null;
            }
        }

        // Execute transaction
        $exec = $this->setSlotAndExecute($exec);

        if ($exec instanceof ErrorInterface) {
            $this->discard();

            return null;
        }

        $this->reset();

        return $exec;
    }

    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        $this->multiStarted = true;
        $response = $this->setSlotAndExecute(new MULTI());

        if ('OK' == $response || $response instanceof Relay) {
            $this->isInitialized = true;
        } else {
            $this->discard();
        }

        return $response;
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

        $response = 'OK' == $this->setSlotAndExecute($watch);

        if ($this->state->check(MultiExecState::CAS)) {
            $this->initializeTransaction();
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        try {
            if (!$this->nodeConnection) {
                return new Status('OK');
            }

            $response = $this->setSlotAndExecute($this->multiStarted ? new DISCARD() : new UNWATCH());

            if ('OK' != $response && !$response instanceof Relay) {
                $this->nodeConnection->disconnect();
            }

            return $response;
        } finally {
            $this->reset();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unwatch()
    {
        return $this->discard();
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
                $this->nodeConnection = $this->connection->getConnectionByCommand($command);
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
        $this->multiStarted = false;
    }
}
