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

use Predis\ClientException;
use Predis\Cluster\CommandHashStrategyInterface;
use Predis\NotSupportedException;
use Predis\ResponseErrorInterface;
use Predis\Cluster\RedisClusterHashStrategy;
use Predis\Command\CommandInterface;

/**
 * Abstraction for Redis cluster (Redis v3.0).
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisCluster implements ClusterConnectionInterface, \IteratorAggregate, \Countable
{
    private $pool;
    private $slots;
    private $slotsMap;
    private $slotsPerNode;
    private $strategy;
    private $connections;

    /**
     * @param ConnectionFactoryInterface $connections Connection factory object.
     */
    public function __construct(ConnectionFactoryInterface $connections = null)
    {
        $this->pool = array();
        $this->slots = array();
        $this->strategy = new RedisClusterHashStrategy();
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
        $this->pool[(string) $connection] = $connection;
        unset(
            $this->slotsMap,
            $this->slotsPerNode
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove(SingleConnectionInterface $connection)
    {
        if (($id = array_search($connection, $this->pool, true)) !== false) {
            unset(
                $this->pool[$id],
                $this->slotsMap,
                $this->slotsPerNode
            );

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
        if (isset($this->pool[$connectionId])) {
            unset(
                $this->pool[$connectionId],
                $this->slotsMap,
                $this->slotsPerNode
            );

            return true;
        }

        return false;
    }

    /**
     * Builds the slots map for the cluster.
     *
     * @return array
     */
    public function buildSlotsMap()
    {
        $this->slotsMap = array();
        $this->slotsPerNode = (int) (16384 / count($this->pool));

        foreach ($this->pool as $connectionID => $connection) {
            $parameters = $connection->getParameters();

            if (!isset($parameters->slots)) {
                continue;
            }

            list($first, $last) = explode('-', $parameters->slots, 2);
            $this->setSlots($first, $last, $connectionID);
        }

        return $this->slotsMap;
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
     * @todo Check type or existence of the specified connection.
     * @todo Cluster loses the slots assigned with this methods when adding / removing connections.
     *
     * @param int $first Initial slot.
     * @param int $last Last slot.
     * @param SingleConnectionInterface|string $connection ID or connection instance.
     */
    public function setSlots($first, $last, $connection)
    {
        if ($first < 0x0000 || $first > 0x3FFF || $last < 0x0000 || $last > 0x3FFF || $last < $first) {
            throw new \OutOfBoundsException("Invalid slot values for $connection: [$first-$last]");
        }

        $this->slotsMap = $this->getSlotsMap() + array_fill($first, $last - $first + 1, (string) $connection);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        $hash = $this->strategy->getHash($command);

        if (!isset($hash)) {
            throw new NotSupportedException("Cannot use {$command->getId()} with redis-cluster");
        }

        $slot = $hash & 0x3FFF;

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        $this->slots[$slot] = $connection = $this->pool[$this->guessNode($slot)];

        return $connection;
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
            throw new \OutOfBoundsException("Invalid slot value [$slot]");
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        return $this->pool[$this->guessNode($slot)];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionId)
    {
        return isset($this->pool[$connectionId]) ? $this->pool[$connectionId] : null;
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

        $index = min((int) ($slot / $this->slotsPerNode), count($this->pool) - 1);
        $nodes = array_keys($this->pool);

        return $nodes[$index];
    }

    /**
     * Handles -MOVED or -ASK replies by re-executing the command on the server
     * specified by the Redis reply.
     *
     * @param CommandInterface $command Command that generated the -MOVE or -ASK reply.
     * @param string $request Type of request (either 'MOVED' or 'ASK').
     * @param string $details Parameters of the MOVED/ASK request.
     * @return mixed
     */
    protected function onMoveRequest(CommandInterface $command, $request, $details)
    {
        list($slot, $host) = explode(' ', $details, 2);
        $connection = $this->getConnectionById($host);

        if (!isset($connection)) {
            $parameters = array('host' => null, 'port' => null);
            list($parameters['host'], $parameters['port']) = explode(':', $host, 2);
            $connection = $this->connections->create($parameters);
        }

        switch ($request) {
            case 'MOVED':
                $this->move($connection, $slot);
                return $this->executeCommand($command);

            case 'ASK':
                return $connection->executeCommand($command);

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
     * Returns the underlying command hash strategy used to hash
     * commands by their keys.
     *
     * @return CommandHashStrategyInterface
     */
    public function getCommandHashStrategy()
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
        return new \ArrayIterator(array_values($this->pool));
    }

    /**
     * Handles -ERR replies from Redis.
     *
     * @param CommandInterface $command Command that generated the -ERR reply.
     * @param ResponseErrorInterface $error Redis error reply object.
     * @return mixed
     */
    protected function handleServerError(CommandInterface $command, ResponseErrorInterface $error)
    {
        list($type, $details) = explode(' ', $error->getMessage(), 2);

        switch ($type) {
            case 'MOVED':
            case 'ASK':
                return $this->onMoveRequest($command, $type, $details);

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
        $reply = $connection->executeCommand($command);

        if ($reply instanceof ResponseErrorInterface) {
            return $this->handleServerError($command, $reply);
        }

        return $reply;
    }
}
