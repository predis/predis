<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Helpers;
use Predis\ClientException;
use Predis\Commands\ICommand;
use Predis\Distribution\IDistributionStrategy;
use Predis\Distribution\HashRing;

/**
 * Abstraction for a cluster of aggregated connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PredisCluster implements IConnectionCluster, \IteratorAggregate
{
    private $_pool;
    private $_distributor;

    /**
     * @param IDistributionStrategy $distributor Distribution strategy used by the cluster.
     */
    public function __construct(IDistributionStrategy $distributor = null)
    {
        $this->_pool = array();
        $this->_distributor = $distributor ?: new HashRing();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        foreach ($this->_pool as $connection) {
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
        foreach ($this->_pool as $connection) {
            $connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->_pool as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(IConnectionSingle $connection)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->alias)) {
            $this->_pool[$parameters->alias] = $connection;
        }
        else {
            $this->_pool[] = $connection;
        }

        $this->_distributor->add($connection, $parameters->weight);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(ICommand $command)
    {
        $cmdHash = $command->getHash($this->_distributor);

        if (isset($cmdHash)) {
            return $this->_distributor->get($cmdHash);
        }

        throw new ClientException(
            sprintf("Cannot send '%s' commands to a cluster of connections", $command->getId())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($id = null)
    {
        $alias = $id ?: 0;

        return isset($this->_pool[$alias]) ? $this->_pool[$alias] : null;
    }


    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key of a Redis value.
     * @return IConnectionSingle
     */
    public function getConnectionByKey($key)
    {
        $hashablePart = Helpers::getKeyHashablePart($key);
        $keyHash = $this->_distributor->generateKey($hashablePart);

        return $this->_distributor->get($keyHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_pool);
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
    {
        $this->getConnection($command)->writeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command)
    {
        return $this->getConnection($command)->executeCommand($command);
    }
}
