<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Profiles\IServerProfile;
use Predis\Network\IConnectionCluster;
use Predis\Network\IConnectionReplication;

/**
 * Interface that must be implemented by classes that provide their own mechanism
 * to create and initialize new instances of Predis\Network\IConnectionSingle.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnectionFactory
{
    /**
     * Defines or overrides the connection class identified by a scheme prefix.
     *
     * @param string $scheme URI scheme identifying the connection class.
     * @param mixed $initializer FQN of a connection class or a callable object for lazy initialization.
     */
    public function define($scheme, $initializer);

    /**
     * Undefines the connection identified by a scheme prefix.
     *
     * @param string $scheme Parameters for the connection.
     */
    public function undefine($scheme);

    /**
     * Creates a new connection object.
     *
     * @param mixed $parameters Parameters for the connection.
     * @return Predis\Network\IConnectionSingle
     */
    public function create($parameters, IServerProfile $profile = null);

    /**
     * Prepares a cluster of connection objects.
     *
     * @param IConnectionCluster Instance of a connection cluster class.
     * @param array $parameters List of parameters for each connection object.
     * @return Predis\Network\IConnectionCluster
     */
    public function createCluster(IConnectionCluster $cluster, $parameters, IServerProfile $profile = null);

    /**
     * Prepares a master / slave replication configuration.
     *
     * @param IConnectionReplication Instance of a connection cluster class.
     * @param array $parameters List of parameters for each connection object.
     * @return Predis\Network\IConnectionReplication
     */
    public function createReplication(IConnectionReplication $replication, $parameters, IServerProfile $profile = null);
}
