<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\Replication\MasterSlaveReplication;
use Predis\Connection\Replication\SentinelReplication;

/**
 * Configures an aggregate connection used for master/slave replication among
 * multiple Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Replication extends Aggregate
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            $value = $this->getConnectionInitializerByString($options, $value);
        }

        if (is_callable($value)) {
            return $this->getConnectionInitializer($options, $value);
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s expects either a string or a callable value, %s given',
                static::class,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    /**
     * Returns a connection initializer (callable) from a descriptive string.
     *
     * Each connection initializer is specialized for the specified replication
     * backend so that all the necessary steps for the configuration of the new
     * aggregate connection are performed inside the initializer and the client
     * receives a ready-to-use connection.
     *
     * Supported configuration values are:
     *
     * - `predis` for unmanaged replication setups
     * - `redis-sentinel` for replication setups managed by redis-sentinel
     * - `sentinel` is an alias of `redis-sentinel`
     *
     * @param OptionsInterface $options     Client options
     * @param string           $description Identifier of a replication backend
     *
     * @return callable
     */
    protected function getConnectionInitializerByString(OptionsInterface $options, string $description)
    {
        switch ($description) {
            case 'sentinel':
            case 'redis-sentinel':
                return function ($parameters, $options, $option) {
                    return new SentinelReplication($options->service, $parameters, $options->connections);
                };

            case 'predis':
                return $this->getDefaultConnectionInitializer($options);

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s expects either `predis`, `sentinel` or `redis-sentinel` as valid string values, `%s` given',
                    static::class,
                    $description
                ));
        }
    }

    /**
     * Returns the default connection initializer.
     *
     * @return callable
     */
    protected function getDefaultConnectionInitializer()
    {
        return function ($parameters, $options, $option) {
            $connection = new MasterSlaveReplication();

            if ($options->autodiscovery) {
                $connection->setConnectionFactory($options->connections);
                $connection->setAutoDiscovery(true);
            }

            return $connection;
        };
    }

    /**
     * {@inheritdoc}
     */
    public static function aggregate(OptionsInterface $options, AggregateConnectionInterface $connection, array $nodes)
    {
        // TODO: at least for now we will replicate the previous behaviour of
        // skipping automatic aggregation when using the redis-sentinel backend
        // because $nodes contains an array of sentinel servers instead of Redis
        // servers and SentinelReplication already gets the list of sentinels in
        // the first argument of its constructor. SentinelReplication::add()
        // actually knows how to handle connections marked with role=sentinel in
        // their parameters but relying on it would require an explicit role to
        // be set by the user and I would like to avoid enforcing that for now.
        if (!$connection instanceof SentinelReplication) {
            parent::aggregate($options, $connection, $nodes);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return $this->getConnectionInitializer(
            $options,
            $this->getDefaultConnectionInitializer()
        );
    }
}
