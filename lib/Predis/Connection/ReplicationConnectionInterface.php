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

/**
 * Defines a group of Redis nodes in a master / slave replication setup.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ReplicationConnectionInterface extends AggregateConnectionInterface
{
    /**
     * Switches the internal connection instance in use.
     *
     * @param string $connection Alias of a connection
     */
    public function switchTo($connection);

    /**
     * Returns the connection instance currently in use by the aggregate
     * connection.
     *
     * @return SingleConnectionInterface
     */
    public function getCurrent();

    /**
     * Returns the connection instance for the master Redis node.
     *
     * @return SingleConnectionInterface
     */
    public function getMaster();

    /**
     * Returns a list of connection instances to slave nodes.
     *
     * @return SingleConnectionInterface
     */
    public function getSlaves();
}
