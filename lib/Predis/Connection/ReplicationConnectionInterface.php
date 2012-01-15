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

/**
 * Defines a group of Redis servers in a master/slave replication configuration.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ReplicationConnectionInterface extends ConnectionInterface
{
    /**
     * Adds a connection instance to the cluster.
     *
     * @param SingleConnectionInterface $connection Instance of a connection.
     */
    public function add(SingleConnectionInterface $connection);

    /**
     * Removes the specified connection instance from the cluster.
     *
     * @param SingleConnectionInterface $connection Instance of a connection.
     * @return Boolean Returns true if the connection was in the pool.
     */
    public function remove(SingleConnectionInterface $connection);

    /**
     * Gets the actual connection instance in charge of the specified command.
     *
     * @param CommandInterface $command Instance of a Redis command.
     * @return SingleConnectionInterface
     */
    public function getConnection(CommandInterface $command);

    /**
     * Retrieves a connection instance from the cluster using an alias.
     *
     * @param string $connectionId Alias of a connection
     * @return SingleConnectionInterface
     */
    public function getConnectionById($connectionId);

    /**
     * Switches the internal connection object being used.
     *
     * @param string $connection Alias of a connection
     */
    public function switchTo($connection);

    /**
     * Retrieves the connection object currently being used.
     *
     * @return SingleConnectionInterface
     */
    public function getCurrent();

    /**
     * Retrieves the connection object to the master Redis server.
     *
     * @return SingleConnectionInterface
     */
    public function getMaster();

    /**
     * Retrieves a list of connection objects to slaves Redis servers.
     *
     * @return SingleConnectionInterface
     */
    public function getSlaves();
}
