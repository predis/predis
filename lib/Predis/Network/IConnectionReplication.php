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

use Predis\Commands\ICommand;

/**
 * Defines a group of Redis servers in a master/slave replication configuration.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnectionReplication extends IConnection
{
    /**
     * Adds a connection instance to the cluster.
     *
     * @param IConnectionSingle $connection Instance of a connection.
     */
    public function add(IConnectionSingle $connection);

    /**
     * Removes the specified connection instance from the cluster.
     *
     * @param IConnectionSingle $connection Instance of a connection.
     * @return Boolean Returns true if the connection was in the pool.
     */
    public function remove(IConnectionSingle $connection);

    /**
     * Gets the actual connection instance in charge of the specified command.
     *
     * @param ICommand $command Instance of a Redis command.
     * @return IConnectionSingle
     */
    public function getConnection(ICommand $command);

    /**
     * Retrieves a connection instance from the cluster using an alias.
     *
     * @param string $connectionId Alias of a connection
     * @return IConnectionSingle
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
     * @return IConnectionSingle
     */
    public function getCurrent();

    /**
     * Retrieves the connection object to the master Redis server.
     *
     * @return IConnectionSingle
     */
    public function getMaster();

    /**
     * Retrieves a list of connection objects to slaves Redis servers.
     *
     * @return IConnectionSingle
     */
    public function getSlaves();
}
