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

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Predis\ClientException;
use Predis\NotSupportedException;
use Predis\Cluster;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Response;

/**
 * Abstraction for Redis cluster (Redis v3.0).
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisCluster implements ClusterConnectionInterface, IteratorAggregate, Countable
{
    private $askSlotsMap = false;
    private $pool = array();
    private $slots = array();
    private $slotsMap;
    private $strategy;
    private $connections;

    /**
     * @param ConnectionFactoryInterface $connections Connection factory object.
     */
    public function __construct(ConnectionFactoryInterface $connections = null)
    {
        $this->strategy = new Cluster\RedisStrategy();
        $this->connections = $connections ?: new ConnectionFactory();
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
        if ($connection = $this->getRandomConnection()) {
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
        $this->pool[(string) $connection] = $connection;
        unset($this->slotsMap);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(SingleConnectionInterface $connection)
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            unset(
                $this->pool[$id],
                $this->slotsMap
            );

            return true;
        }

        return false;
    }

    /**
     * Removes a connection instance using its alias or index.
     *
     * @param string $connectionID Alias or index of a connection.
     * @return bool Returns true if the connection was in the pool.
     */
    public function removeById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            unset(
                $this->pool[$connectionID],
                $this->slotsMap
            );

            return true;
        }

        return false;
    }

    /**
     * Builds the slots map for the cluster.
     */
    public function buildSlotsMap()
    {
        $this->slotsMap = array();

        foreach ($this->pool as $connectionID => $connection) {
            $parameters = $connection->getParameters();

            if (!isset($parameters->slots)) {
                continue;
            }

            $slots = explode('-', $parameters->slots, 2);
            $this->setSlots($slots[0], $slots[1], $connectionID);
        }
    }

    /**
     * Builds the slots map for the cluster by asking the current configuration
     * to one of the nodes in the cluster.
     */
    public function askSlotsMap()
    {
        if (!$connection = $this->getRandomConnection()) {
            return array();
        }

        $cmdCluster = RawCommand::create('CLUSTER', 'NODES');
        $response = $connection->executeCommand($cmdCluster);

        $nodes = explode("\n", $response, -1);
        $count = count($nodes);

        for ($i = 0; $i < $count; $i++) {
            $node = explode(' ', $nodes[$i], 9);
            $slots = explode('-', $node[8], 2);

            if ($node[1] === ':0') {
                $this->setSlots($slots[0], $slots[1], (string) $connection);
            } else {
                $this->setSlots($slots[0], $slots[1], $node[1]);
            }
        }
    }

    /**
     * Returns the current slots map for the cluster.
     *
     * @return array
     */
    public function getSlotsMap()
    {
        if (!isset($this->slotsMap)) {
            $this->slotsMap = array();
        }

        return $this->slotsMap;
    }

    /**
     * Preassociate a connection to a set of slots to avoid runtime guessing.
     *
     * @param int $first Initial slot.
     * @param int $last Last slot.
     * @param SingleConnectionInterface|string $connection ID or connection instance.
     */
    public function setSlots($first, $last, $connection)
    {
        if ($first < 0x0000 || $first > 0x3FFF ||
            $last < 0x0000 || $last > 0x3FFF ||
            $last < $first
        ) {
            throw new OutOfBoundsException(
                "Invalid slot range for $connection: [$first-$last]"
            );
        }

        $slots = array_fill($first, $last - $first + 1, (string) $connection);
        $this->slotsMap = $this->getSlotsMap() + $slots;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        $hash = $this->strategy->getHash($command);

        if (!isset($hash)) {
            throw new NotSupportedException(
                "Cannot use {$command->getId()} with redis-cluster"
            );
        }

        $slot = $hash & 0x3FFF;

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        } else {
            return $this->getConnectionBySlot($slot);
        }
    }

    /**
     * Returns a random connection from the pool.
     *
     * @return SingleConnectionInterface
     */
    protected function getRandomConnection()
    {
        if ($this->pool) {
            return $this->pool[array_rand($this->pool)];
        }
    }

    /**
     * Returns the connection associated to the specified slot.
     *
     * @param int $slot Slot ID.
     * @return SingleConnectionInterface
     */
    public function getConnectionBySlot($slot)
    {
        if ($slot < 0x0000 || $slot > 0x3FFF) {
            throw new OutOfBoundsException("Invalid slot [$slot]");
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        $connectionID = $this->guessNode($slot);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $host = explode(':', $connectionID, 2);
            $connection = $this->connections->create(array(
                'host' => $host[0],
                'port' => $host[1],
            ));

            $this->pool[$connectionID] = $connection;
        }

        return $this->slots[$slot] = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            return $this->pool[$connectionID];
        }
    }

    /**
     * Tries guessing the correct node associated to the given slot using a precalculated
     * slots map or the same logic used by redis-trib to initialize a redis cluster.
     *
     * @param int $slot Slot ID.
     * @return string
     */
    protected function guessNode($slot)
    {
        if (!isset($this->slotsMap)) {
            $this->buildSlotsMap();
        }

        if (isset($this->slotsMap[$slot])) {
            return $this->slotsMap[$slot];
        }

        $count = count($this->pool);
        $index = min((int) ($slot / (int) (16384 / $count)), $count - 1);
        $nodes = array_keys($this->pool);

        return $nodes[$index];
    }

    /**
     * Handles -MOVED and -ASK responses by re-executing the command on the node
     * specified by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -MOVE or -ASK response.
     * @param string $request Type of request (either 'MOVED' or 'ASK').
     * @param string $details Parameters of the MOVED/ASK request.
     * @return mixed
     */
    protected function onMoveRequest(CommandInterface $command, $request, $details)
    {
        list($slot, $host) = explode(' ', $details, 2);
        $connection = $this->getConnectionById($host);

        if (!$connection) {
            $host = explode(':', $host, 2);

            $connection = $this->connections->create(array(
                'host' => $host[0],
                'port' => $host[1],
            ));
        }

        switch ($request) {
            case 'MOVED':
                if ($this->askSlotsMap) {
                    $this->askSlotsMap();
                }

                $this->move($connection, $slot);
                $response = $this->executeCommand($command);

                return $response;

            case 'ASK':
                $connection->executeCommand(RawCommand::create('ASKING'));
                $response = $connection->executeCommand($command);

                return $response;

            default:
                throw new ClientException("Unexpected request type for a move request: $request");
        }
    }

    /**
     * Assign the connection instance to a new slot and adds it to the
     * pool if the connection was not already part of the pool.
     *
     * @param SingleConnectionInterface $connection Connection instance
     * @param int $slot Target slot.
     */
    protected function move(SingleConnectionInterface $connection, $slot)
    {
        $this->pool[(string) $connection] = $connection;
        $this->slots[(int) $slot] = $connection;
    }

    /**
     * Returns the underlying hash strategy used to hash commands by their keys.
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
        return new ArrayIterator(array_values($this->pool));
    }

    /**
     * Handles -ERR responses from Redis.
     *
     * @param CommandInterface $command Command that generated the -ERR response.
     * @param Response\ErrorInterface $error Redis error response object.
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, Response\ErrorInterface $error)
    {
        $details = explode(' ', $error->getMessage(), 2);

        switch ($details[0]) {
            case 'MOVED':
            case 'ASK':
                return $this->onMoveRequest($command, $details[0], $details[1]);

            default:
                return $error;
        }
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
        $connection = $this->getConnection($command);
        $response = $connection->executeCommand($command);

        if ($response instanceof Response\ErrorInterface) {
            return $this->onErrorResponse($command, $response);
        }

        return $response;
    }

    /**
     * Instruct the cluster to fetch the slots map from one of the nodes.
     *
     * @param bool $value Enable or disable fetching the slots map.
     */
    public function setAskSlotsMap($value)
    {
        $this->askSlotsMap = (bool) $value;
    }
}
