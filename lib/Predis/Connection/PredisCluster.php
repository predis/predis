<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Countable;
use IteratorAggregate;
use Predis\NotSupportedException;
use Predis\Cluster;
use Predis\Cluster\Distributor;
use Predis\Command\CommandInterface;

/**
 * Abstraction for a cluster of aggregated connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @todo Add the ability to remove connections from pool.
 */
class PredisCluster implements ClusterConnectionInterface, IteratorAggregate, Countable
{
    private $pool;
    private $strategy;
    private $distributor;

    /**
     * @param Distributor\DistributorInterface $distributor Distribution strategy used by cluster.
     */
    public function __construct(Distributor\DistributorInterface $distributor = null)
    {
        $distributor = $distributor ?: new Distributor\HashRing();

        $this->pool = array();
        $this->strategy = new Cluster\PredisStrategy($distributor->getHashGenerator());
        $this->distributor = $distributor;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        foreach ($this->pool as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        foreach ($this->pool as $connection) {
            $connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->pool as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(SingleConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->alias)) {
            $this->pool[$parameters->alias] = $connection;
        } else {
            $this->pool[] = $connection;
        }

        $weight = isset($parameters->weight) ? $parameters->weight : null;
        $this->distributor->add($connection, $weight);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(SingleConnectionInterface $connection)
    {
        if (($id = array_search($connection, $this->pool, true)) !== false) {
            unset($this->pool[$id]);
            $this->distributor->remove($connection);

            return true;
        }

        return false;
    }

    /**
     * Removes a connection instance using its alias or index.
     *
     * @param string $connectionId Alias or index of a connection.
     * @return Boolean Returns true if the connection was in the pool.
     */
    public function removeById($connectionId)
    {
        if ($connection = $this->getConnectionById($connectionId)) {
            return $this->remove($connection);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        $hash = $this->strategy->getHash($command);

        if (!isset($hash)) {
            throw new NotSupportedException(
                "Cannot use {$command->getId()} with a cluster of connections"
            );
        }

        $node = $this->distributor->get($hash);

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionId)
    {
        return isset($this->pool[$connectionId]) ? $this->pool[$connectionId] : null;
    }

    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key of a Redis value.
     * @return SingleConnectionInterface
     */
    public function getConnectionByKey($key)
    {
        $hash = $this->strategy->getKeyHash($key);
        $node = $this->distributor->get($hash);

        return $node;
    }

    /**
     * Returns the underlying command hash strategy used to hash
     * commands by their keys.
     *
     * @return Cluster\StrategyInterface
     */
    public function getClusterStrategy()
    {
        return $this->strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->getConnection($command)->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->getConnection($command)->executeCommand($command);
    }

    /**
     * Executes the specified Redis command on all the nodes of a cluster.
     *
     * @param CommandInterface $command A Redis command.
     * @return array
     */
    public function executeCommandOnNodes(CommandInterface $command)
    {
        $responses = array();

        foreach ($this->pool as $connection) {
            $responses[] = $connection->executeCommand($command);
        }

        return $responses;
    }
}
