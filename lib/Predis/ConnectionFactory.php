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
use Predis\Network\IConnectionSingle;
use Predis\Network\IConnectionCluster;
use Predis\Network\IConnectionReplication;
use Predis\Profiles\ServerProfile;

/**
 * Provides a default factory for Redis connections that maps URI schemes
 * to connection classes implementing the Predis\Network\IConnectionSingle
 * interface.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionFactory implements IConnectionFactory
{
    private $schemes;

    /**
     * Initializes a new instance of the default connection factory class used by Predis.
     */
    public function __construct()
    {
        $this->schemes = $this->getDefaultSchemes();
    }

    /**
     * Returns a named array that maps URI schemes to connection classes.
     *
     * @return array Map of URI schemes and connection classes.
     */
    protected function getDefaultSchemes()
    {
        return array(
            'tcp' => 'Predis\Network\StreamConnection',
            'unix' => 'Predis\Network\StreamConnection',
            'http' => 'Predis\Network\WebdisConnection',
        );
    }

    /**
     * Checks if the provided argument represents a valid connection class
     * implementing the Predis\Network\IConnectionSingle interface. Optionally,
     * callable objects are used for lazy initialization of connection objects.
     *
     * @param mixed $initializer FQN of a connection class or a callable for lazy initialization.
     * @return mixed
     */
    protected function checkInitializer($initializer)
    {
        if (is_callable($initializer)) {
            return $initializer;
        }

        $initializerReflection = new \ReflectionClass($initializer);

        if (!$initializerReflection->isSubclassOf('Predis\Network\IConnectionSingle')) {
            throw new \InvalidArgumentException(
                'A connection initializer must be a valid connection class or a callable object'
            );
        }

        return $initializer;
    }

    /**
     * {@inheritdoc}
     */
    public function define($scheme, $initializer)
    {
        $this->schemes[$scheme] = $this->checkInitializer($initializer);
    }

    /**
     * {@inheritdoc}
     */
    public function undefine($scheme)
    {
        unset($this->schemes[$scheme]);
    }

    /**
     * {@inheritdoc}
     */
    public function create($parameters, IServerProfile $profile = null)
    {
        if (!$parameters instanceof IConnectionParameters) {
            $parameters = new ConnectionParameters($parameters ?: array());
        }

        $scheme = $parameters->scheme;
        if (!isset($this->schemes[$scheme])) {
            throw new \InvalidArgumentException("Unknown connection scheme: $scheme");
        }

        $initializer = $this->schemes[$scheme];
        if (!is_callable($initializer)) {
            $connection = new $initializer($parameters);
            $this->prepareConnection($connection, $profile ?: ServerProfile::getDefault());

            return $connection;
        }

        $connection = call_user_func($initializer, $parameters, $profile);
        if (!$connection instanceof IConnectionSingle) {
            throw new \InvalidArgumentException(
                'Objects returned by connection initializers must implement ' .
                'the Predis\Network\IConnectionSingle interface'
            );
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function createCluster(IConnectionCluster $cluster, $parameters, IServerProfile $profile = null)
    {
        foreach ($parameters as $node) {
            $cluster->add($node instanceof IConnectionSingle ? $node : $this->create($node, $profile));
        }

        return $cluster;
    }

    /**
     * {@inheritdoc}
     */
    public function createReplication(IConnectionReplication $replication, $parameters, IServerProfile $profile = null)
    {
        foreach ($parameters as $node) {
            $replication->add($node instanceof IConnectionSingle ? $node : $this->create($node, $profile));
        }

        return $replication;
    }

    /**
     * Prepares a connection object after its initialization.
     *
     * @param IConnectionSingle $connection Instance of a connection object.
     * @param IServerProfile $profile $connection Instance of a connection object.
     */
    protected function prepareConnection(IConnectionSingle $connection, IServerProfile $profile)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->password)) {
            $command = $profile->createCommand('auth', array($parameters->password));
            $connection->pushInitCommand($command);
        }

        if (isset($parameters->database)) {
            $command = $profile->createCommand('select', array($parameters->database));
            $connection->pushInitCommand($command);
        }
    }
}
