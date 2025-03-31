<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param  CommandInterface     $command
     * @return mixed
     * @throws TransactionException
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Executes previously opened transaction context.
     *
     * @return mixed
     * @throws TransactionException
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
     * @param  array                $keys
     * @return mixed
     * @throws TransactionException
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
