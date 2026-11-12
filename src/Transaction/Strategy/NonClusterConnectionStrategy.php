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
use Predis\CommunicationException;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\RelayConnection;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\Response\Status;
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
     * Node connection holding the transaction from WATCH or MULTI until EXEC,
     * DISCARD or UNWATCH. Commands are not retried while it is set, since they
     * would run outside of the transaction on another connection. Once it is
     * lost, commands fail until discard() or unwatch().
     *
     * @var NodeConnectionInterface|null
     */
    private $nodeConnection;

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
        return 'OK' == $this->multi();
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

        return $this->send($command);
    }

    /**
     * {@inheritDoc}
     */
    public function executeTransaction()
    {
        try {
            return $this->executeBypassingTransaction(new EXEC())->getResponse();
        } finally {
            $this->releaseConnection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        $response = $this->executeBypassingTransaction(new MULTI())->getResponse();
        $this->holdConnection();

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function watch(array $keys)
    {
        $watch = new WATCH();
        $watch->setArguments($keys);
        $response = $this->executeBypassingTransaction($watch)->getResponse();
        $this->holdConnection();

        return $response;
    }

    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function unwatch()
    {
        $this->nodeConnection = null;

        return $this->send(new UNWATCH());
    }

    /**
     * Ends the transaction even when DISCARD fails, closing the connection so
     * that it cannot stay in MULTI. A lost connection has nothing left to
     * discard: closing it ended the transaction on the server.
     *
     * {@inheritDoc}
     */
    public function discard()
    {
        if ($this->isConnectionLost()) {
            $this->nodeConnection = null;

            return new Status('OK');
        }

        $connection = $this->nodeConnection ?: $this->connection;

        try {
            return $this->executeBypassingTransaction(new DISCARD())->getResponse();
        } catch (ServerException $exception) {
            $connection->disconnect();

            throw $exception;
        } finally {
            $this->nodeConnection = null;
        }
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
        try {
            $response = $this->send($command);
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
     * Sends a command to the connection holding the transaction, or with
     * retries while the transaction has not reached any connection yet.
     *
     * @param  CommandInterface $command
     * @return mixed
     * @throws Throwable
     */
    private function send(CommandInterface $command)
    {
        if ($connection = $this->nodeConnection) {
            if (!$connection->isConnected()) {
                throw new ConnectionException($connection, 'The connection holding the transaction was lost.');
            }

            try {
                return $connection->executeCommand($command);
            } catch (CommunicationException $exception) {
                $connection->disconnect();

                throw $exception;
            }
        }

        $retry = $this->connection->getParameters()->retry;

        return $retry->callWithRetry(
            function () use ($command) {
                return $this->connection->executeCommand($command);
            }, function (Throwable $e) {
                if ($e instanceof CommunicationException) {
                    $e->getConnection()->disconnect();
                }
            }
        );
    }

    /**
     * Keeps the node connection that has just received WATCH or MULTI.
     */
    private function holdConnection(): void
    {
        if (null === $this->nodeConnection) {
            $this->nodeConnection = $this->connection instanceof ReplicationInterface
                ? $this->connection->getCurrent()
                : $this->connection;
        }
    }

    /**
     * Stops holding the connection once it has replied to EXEC. A lost
     * connection stays held until discard() or unwatch().
     */
    private function releaseConnection(): void
    {
        if (!$this->isConnectionLost()) {
            $this->nodeConnection = null;
        }
    }

    private function isConnectionLost(): bool
    {
        return $this->nodeConnection && !$this->nodeConnection->isConnected();
    }
}
