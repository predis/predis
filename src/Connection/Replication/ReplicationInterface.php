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

namespace Predis\Connection\Replication;

use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\NodeConnectionInterface;

/**
 * Defines a group of Redis nodes in a master / slave replication setup.
 */
interface ReplicationInterface extends AggregateConnectionInterface
{
    /**
     * Switches the internal connection in use to the master server.
     */
    public function switchToMaster();

    /**
     * Switches the internal connection in use to a random slave server.
     */
    public function switchToSlave();

    /**
     * Returns the connection in use by the replication backend.
     *
     * @return NodeConnectionInterface
     */
    public function getCurrent();

    /**
     * Returns the connection to the master server.
     *
     * @return NodeConnectionInterface
     */
    public function getMaster();

    /**
     * Returns a list of connections to slave servers.
     *
     * @return NodeConnectionInterface[]
     */
    public function getSlaves();
}
