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

namespace Predis\Connection;

use Predis\Command\CommandInterface;

/**
 * Defines a virtual connection composed of multiple connection instances to
 * single Redis nodes.
 */
interface AggregateConnectionInterface extends ConnectionInterface
{
    /**
     * Adds a connection instance to the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    public function add(NodeConnectionInterface $connection);

    /**
     * Removes the specified connection instance from the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     *
     * @return bool Returns true if the connection was in the pool.
     */
    public function remove(NodeConnectionInterface $connection);

    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return NodeConnectionInterface
     */
    public function getConnectionByCommand(CommandInterface $command);

    /**
     * Returns a connection instance from the aggregate connection by its alias.
     *
     * @param string $connectionID Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionById($connectionID);
}
