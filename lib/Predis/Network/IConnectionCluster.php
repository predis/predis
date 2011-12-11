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
 * Defines a cluster of Redis servers formed by aggregating multiple
 * connection objects.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnectionCluster extends IConnection
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
}
