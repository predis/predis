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
use Predis\Command\Redis\DISCARD;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\UNWATCH;
use Predis\Command\Redis\WATCH;
use Predis\CommunicationException;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\RelayConnection;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\TimeoutException;
use Predis\Transaction\MultiExecState;
use Predis\Transaction\Response\BypassTransactionResponse;
use Throwable;

/**
 * Defines strategy for connections that operates on non-distributed hash slots.
 */
abstract class NonClusterConnectionStrategy implements StrategyInterface
{
    /**
     * @var NodeConnectionInterface|ReplicationInterface
     */
    protected $connection;

    /**
     * @var MultiExecState
     */
    protected $state;

    /**
     * @param NodeConnectionInterface|ReplicationInterface $connection
     */
    public function __construct($connection, MultiExecState $state)
    {
        $this->connection = $connection;
        $this->state = $state;
    }

    /**
     * {@inheritDoc}
     */
    public function initializeTransaction(): bool
    {
        return 'OK' == $this->executeBypassingTransaction(new MULTI())->getResponse();
    }

    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function executeCommand(CommandInterface $command)
    {
        if ($this->state->isCAS()) {
            return $this->executeBypassingTransaction($command);
        }

        $retry = $this->connection->getParameters()->retry;

        return $retry->callWithRetry(
            function () use ($command) {
                return $this->connection->executeCommand($command);
            }, function (CommunicationException $e) {
                $this->onFailCallback($e);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function executeTransaction()
    {
        return $this->executeBypassingTransaction(new EXEC())->getResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        return $this->executeBypassingTransaction(new MULTI())->getResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        $watch = new WATCH();
        $watch->setArguments($keys);

        return $this->executeBypassingTransaction($watch)->getResponse();
    }

    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function unwatch()
    {
        $retry = $this->connection->getParameters()->retry;

        return $retry->callWithRetry(
            function () {
                return $this->connection->executeCommand(new UNWATCH());
            }, function (CommunicationException $e) {
                $this->onFailCallback($e);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        return $this->executeBypassingTransaction(new DISCARD())->getResponse();
    }

    /**
     * Executes a Redis command bypassing the transaction logic.
     *
     * @param  CommandInterface          $command
     * @return BypassTransactionResponse
     * @throws ServerException|Throwable
     */
    protected function executeBypassingTransaction(CommandInterface $command): BypassTransactionResponse
    {
        $retry = $this->connection->getParameters()->retry;

        try {
            $response = $retry->callWithRetry(
                function () use ($command) {
                    return $this->connection->executeCommand($command);
                }, function (CommunicationException $e) {
                    $this->onFailCallback($e);
                }
            );
        } catch (ServerException $exception) {
            if (!$this->connection instanceof RelayConnection) {
                throw $exception;
            }

            if (strcasecmp($command->getId(), 'EXEC') != 0) {
                throw $exception;
            }

            if (!strpos($exception->getMessage(), 'RELAY_ERR_REDIS')) {
                throw $exception;
            }

            return new BypassTransactionResponse(null);
        }

        if ($response instanceof ErrorInterface) {
            throw new ServerException($response->getMessage());
        }

        return new BypassTransactionResponse($response);
    }

    /**
     * Handle communication exception.
     *
     * @param  CommunicationException $e
     * @return void
     */
    private function onFailCallback(CommunicationException $e)
    {
        $connection = $e->getConnection();

        if ($connection instanceof NodeConnectionInterface) {
            $connection->disconnect();

            return;
        }

        if ($e instanceof ConnectionException) {
            $nodeConnection = $e->getConnection();

            if ($nodeConnection) {
                $nodeConnection->disconnect();
                $this->connection->remove($nodeConnection);
            }
        }

        if ($e instanceof TimeoutException) {
            $nodeConnection = $e->getConnection();

            if ($nodeConnection) {
                $nodeConnection->disconnect();
            }
        }
    }
}
