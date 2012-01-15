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

use Predis\Command\CommandInterface;
use Predis\Distribution\DistributionStrategyInterface;
use Predis\Helpers;
use Predis\ClientException;
use Predis\NotSupportedException;
use Predis\Distribution\HashRing;

/**
 * Abstraction for a cluster of aggregated connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @todo Add the ability to remove connections from pool.
 */
class PredisCluster implements ClusterConnectionInterface, \IteratorAggregate, \Countable
{
    private $pool;
    private $distributor;

    /**
     * @param DistributionStrategyInterface $distributor Distribution strategy used by the cluster.
     */
    public function __construct(DistributionStrategyInterface $distributor = null)
    {
        $this->pool = array();
        $this->distributor = $distributor ?: new HashRing();
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
        }
        else {
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
        $cmdHash = $command->getHash($this->distributor);

        if (isset($cmdHash)) {
            return $this->distributor->get($cmdHash);
        }

        $message = sprintf("Cannot send '%s' commands to a cluster of connections", $command->getId());
        throw new NotSupportedException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($id = null)
    {
        $alias = $id ?: 0;

        return isset($this->pool[$alias]) ? $this->pool[$alias] : null;
    }


    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key of a Redis value.
     * @return SingleConnectionInterface
     */
    public function getConnectionByKey($key)
    {
        $hashablePart = Helpers::extractKeyTag($key);
        $keyHash = $this->distributor->hash($hashablePart);

        return $this->distributor->get($keyHash);
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
    public function writeCommand(CommandInterface $command)
    {
        $this->getConnection($command)->writeCommand($command);
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
}
