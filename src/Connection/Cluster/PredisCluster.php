<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Cluster;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Predis\Cluster\PredisStrategy;
use Predis\Cluster\StrategyInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\NotSupportedException;
use ReturnTypeWillChange;
use Traversable;

/**
 * Abstraction for a cluster of aggregate connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 */
class PredisCluster implements ClusterInterface, IteratorAggregate, Countable
{
    /**
     * @var NodeConnectionInterface[]
     */
    private $pool = [];

    /**
     * @var NodeConnectionInterface[]
     */
    private $aliases = [];

    /**
     * @var StrategyInterface
     */
    private $strategy;

    /**
     * @var \Predis\Cluster\Distributor\DistributorInterface
     */
    private $distributor;

    /**
     * @param StrategyInterface $strategy Optional cluster strategy.
     */
    public function __construct(StrategyInterface $strategy = null)
    {
        $this->strategy = $strategy ?: new PredisStrategy();
        $this->distributor = $this->strategy->getDistributor();
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
    public function add(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        $this->pool[(string) $connection] = $connection;

        if (isset($parameters->alias)) {
            $this->aliases[$parameters->alias] = $connection;
        }

        $this->distributor->add($connection, $parameters->weight);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            unset($this->pool[$id]);
            $this->distributor->remove($connection);

            if ($this->aliases && $alias = $connection->getParameters()->alias) {
                unset($this->aliases[$alias]);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionByCommand(CommandInterface $command)
    {
        $slot = $this->strategy->getSlot($command);

        if (!isset($slot)) {
            throw new NotSupportedException(
                "Cannot use '{$command->getId()}' over clusters of connections."
            );
        }

        return $this->distributor->getBySlot($slot);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($id)
    {
        return $this->pool[$id] ?? null;
    }

    /**
     * Returns a connection instance by its alias.
     *
     * @param string $alias Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionByAlias($alias)
    {
        return $this->aliases[$alias] ?? null;
    }

    /**
     * Retrieves a connection instance by slot.
     *
     * @param string $slot Slot name.
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionBySlot($slot)
    {
        return $this->distributor->getBySlot($slot);
    }

    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key string.
     *
     * @return NodeConnectionInterface
     */
    public function getConnectionByKey($key)
    {
        $hash = $this->strategy->getSlotByKey($key);

        return $this->distributor->getBySlot($hash);
    }

    /**
     * Returns the underlying command hash strategy used to hash commands by
     * using keys found in their arguments.
     *
     * @return StrategyInterface
     */
    public function getClusterStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->pool);
    }

    /**
     * @return Traversable<string, NodeConnectionInterface>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->getConnectionByCommand($command)->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->getConnectionByCommand($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->getConnectionByCommand($command)->executeCommand($command);
    }
}
