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

use Predis\Profile\ServerProfileInterface;
use Predis\Connection\SingleConnectionInterface;
use Predis\Connection\ClusterConnectionInterface;
use Predis\Connection\ReplicationConnectionInterface;
use Predis\Profile\ServerProfile;

/**
 * Provides a default factory for Redis connections that maps URI schemes
 * to connection classes implementing Predis\Connection\SingleConnectionInterface.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionFactory implements ConnectionFactoryInterface
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
            'tcp' => 'Predis\Connection\StreamConnection',
            'unix' => 'Predis\Connection\StreamConnection',
            'http' => 'Predis\Connection\WebdisConnection',
        );
    }

    /**
     * Checks if the provided argument represents a valid connection class
     * implementing Predis\Connection\SingleConnectionInterface. Optionally,
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

        if (!$initializerReflection->isSubclassOf('Predis\Connection\SingleConnectionInterface')) {
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
    public function create($parameters, ServerProfileInterface $profile = null)
    {
        if (!$parameters instanceof ConnectionParametersInterface) {
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
        if (!$connection instanceof SingleConnectionInterface) {
            throw new \InvalidArgumentException(
                'Objects returned by connection initializers must implement ' .
                'Predis\Connection\SingleConnectionInterface'
            );
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function createCluster(ClusterConnectionInterface $cluster, $parameters, ServerProfileInterface $profile = null)
    {
        foreach ($parameters as $node) {
            $cluster->add($node instanceof SingleConnectionInterface ? $node : $this->create($node, $profile));
        }

        return $cluster;
    }

    /**
     * {@inheritdoc}
     */
    public function createReplication(ReplicationConnectionInterface $replication, $parameters, ServerProfileInterface $profile = null)
    {
        foreach ($parameters as $node) {
            $replication->add($node instanceof SingleConnectionInterface ? $node : $this->create($node, $profile));
        }

        return $replication;
    }

    /**
     * Prepares a connection object after its initialization.
     *
     * @param SingleConnectionInterface $connection Instance of a connection object.
     * @param ServerProfileInterface $profile $connection Instance of a connection object.
     */
    protected function prepareConnection(SingleConnectionInterface $connection, ServerProfileInterface $profile)
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
