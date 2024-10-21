<?php

namespace Predis\Transaction\Strategy;

use Predis\Command\CommandInterface;
use Predis\Command\Redis\DISCARD;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\UNWATCH;
use Predis\Command\Redis\WATCH;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Response\Error;
use Predis\Response\Status;
use Predis\Transaction\Exception\TransactionException;
use Predis\Transaction\MultiExecState;
use SplQueue;


class ClusterConnectionStrategy implements StrategyInterface
{
    /**
     * @var ClusterInterface $connection
     */
    private $connection;

    /**
     * Server-matching slot of the current transaction.
     *
     * @var ?int $slot
     */
    private $slot;

    /**
     * In cluster environment it needs to be queued to ensure
     * that all commands will point to the same node.
     *
     * @var SplQueue $commandsQueue
     */
    private $commandsQueue;

    /**
     * Shows if transaction context was initialized.
     *
     * @var bool $isInitialized
     */
    private $isInitialized = false;

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
            throw new TransactionException("Transaction context should be initialized first");
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
                "To be able to execute a transaction against cluster, all commands should operate on the same hash slot"
            );
        }

        $this->commandsQueue->enqueue($command);
        return new Status("QUEUED");
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
            throw new TransactionException("Transaction context should be initialized first");
        }

        $exec = new EXEC();

        /** @var MULTI $multi */
        $multi = $this->commandsQueue->dequeue();

        // Begin transaction
        if ('OK' != $this->setSlotAndExecute($multi)) {
            return null;
        }

        // Transaction body
        while (!$this->commandsQueue->isEmpty()) {
            /** @var CommandInterface $command */
            $command = $this->commandsQueue->dequeue();

            if ('QUEUED' != $this->setSlotAndExecute($command)) {
                return null;
            }
        }

        // Execute transaction
        $exec = $this->setSlotAndExecute($exec);
        $this->slot = null;

        return $exec;
    }

    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        $response = $this->setSlotAndExecute(new MULTI());

        if ('OK' == $response) {
            $this->isInitialized = true;
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        if (!$this->clusterStrategy->checkSameSlotForKeys($keys)) {
            throw new TransactionException("WATCHed keys should point to the same hash slot");
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
        return $this->setSlotAndExecute(new DISCARD());
    }

    /**
     * {@inheritDoc}
     */
    public function unwatch()
    {
        return $this->setSlotAndExecute(new UNWATCH());
    }

    /**
     * Assigns slot to a command and executes.
     *
     * @param CommandInterface $command
     * @return mixed
     */
    private function setSlotAndExecute(CommandInterface $command)
    {
        if (null !== $this->slot) {
            $command->setSlot($this->slot);
        }

        return $this->connection->executeCommand($command);
    }
}
