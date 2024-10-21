<?php

namespace Predis\Transaction\Strategy;

use Predis\Command\CommandInterface;
use Predis\Transaction\Exception\TransactionException;

interface StrategyInterface
{
    /**
     * Initialize transaction context.
     *
     * @return bool
     */
    public function initializeTransaction(): bool;

    /**
     * Executes a given command in a transaction context.
     *
     * @param CommandInterface $command
     * @return mixed
     * @throws TransactionException
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Executes previously opened transaction context.
     *
     * @throws TransactionException
     * @return mixed
     */
    public function executeTransaction();

    /**
     * Sends MULTI command.
     *
     * @return mixed
     */
    public function multi();

    /**
     * Enable WATCH for given keys.
     *
     * @param array $keys
     * @throws TransactionException
     * @return mixed
     */
    public function watch(array $keys);

    /**
     * Disable previously enabled WATCH.
     *
     * @return mixed
     */
    public function unwatch();

    /**
     * Discards active transaction context.
     *
     * @return mixed
     */
    public function discard();
}
