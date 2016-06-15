<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Replication;

use Predis\ClientContextInterface;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\Replication\ReplicationInterface;

/**
 * Abstraction for the WAIT command.
 *
 * This can be used with a client connected to a single node or configured with
 * both cluster and replication backends.
 *
 * @see http://redis.io/commands/wait
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class WaitContext
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CommandInterface[]
     */
    private $commands = array();

    /**
     * @param ClientInterface $client Client instance.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
        $command = $this->client->createCommand($commandID, $arguments);
        $response = $this->executeCommand($command);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $response = $this->client->executeCommand($command);
        $this->commands[] = $command;

        return $response;
    }

    /**
     * Executes the WAIT command against a connection.
     *
     * @param ConnectionInterface $connection [description]
     * @param int $numslaves Minimum number of slaves for acknowledgment.
     * @param int $timeout   Timeout in milliseconds for acknowledgment.
     *
     * @return int
     */
    private function executeWaitCommand(ConnectionInterface $connection, $numslaves, $timeout)
    {
        $response = $connection->executeCommand(
            RawCommand::create('WAIT', $numslaves, $timeout)
        );

        return $response;
    }

    /**
     * Returns the connection for WAIT from a replication backend.
     *
     * @param ReplicationInterface $replication Replication connection backend.
     *
     * @return ConnectionInterface
     */
    private function getConnectionFromReplication(ReplicationInterface $replication)
    {
        return $replication->getCurrent();
    }

    /**
     * Returns the connection for WAIT from a cluster backend.
     *
     * @param ClusterInterface $cluster Cluster connection backend.
     *
     * @return ConnectionInterface
     */
    private function getConnectionFromCluster(ClusterInterface $cluster)
    {
        $command = reset($this->commands);
        $slot = $command->getSlot();

        foreach ($this->commands as $command) {
            if ($slot !== $command->getSlot()) {
                throw new ClientException('Cross-slot operations are not allowed');
            }
        }

        // TODO: we should actually fetch the connection by slot but this is not
        // currently supported by all our cluster backends.
        $connection = $cluster->getConnection($command);

        return $connection;
    }

    /**
     * Executes the WAIT command and returns the status of acknowledgment.
     *
     * When the client is operating in replication mode WAIT is executed against
     * the connection currently in use by the underlying backend. On the other
     * hand when it is operating in cluster mode WAIT is executed against only
     * one connection as cross-slot operations are not allowed.
     *
     * @param int $numslaves Minimum number of slaves for acknowledgment.
     * @param int $timeout   Timeout in milliseconds for acknowledgment.
     * @param int &$slaves   Set with the number of slaves that acknowledged the writes.
     *
     * @return bool
     */
    public function wait($numslaves, $timeout, &$slaves = 0)
    {
        if (!$this->commands) {
            return false;
        }

        $connection = $this->client->getConnection();

        if ($connection instanceof ReplicationInterface) {
            $connection = $this->getConnectionFromReplication($connection);
        } elseif ($connection instanceof ClusterInterface) {
            $connection = $this->getConnectionFromCluster($connection);
        }

        $this->commands = array();

        $slaves = $this->executeWaitCommand($connection, $numslaves, $timeout);
        $acknowledged = $slaves >= $numslaves;

        return $acknowledged;
    }

    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
