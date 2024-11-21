<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\NodeConnectionInterface;

/**
 * Client option for configuring generic aggregate connections.
 *
 * The only value accepted by this option is a callable that must return a valid
 * connection instance of Predis\Connection\AggregateConnectionInterface when
 * invoked by the client to create a new aggregate connection instance.
 *
 * Creation and configuration of the aggregate connection is up to the user.
 */
class Aggregate implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (!is_callable($value)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a callable object acting as an aggregate connection initializer',
                static::class
            ));
        }

        return $this->getConnectionInitializer($options, $value);
    }

    /**
     * Wraps a user-supplied callable used to create a new aggregate connection.
     *
     * When the original callable acting as a connection initializer is executed
     * by the client to create a new aggregate connection, it will receive the
     * following arguments:
     *
     * - $parameters (same as passed to Predis\Client::__construct())
     * - $options (options container, Predis\Configuration\OptionsInterface)
     * - $option (current option, Predis\Configuration\OptionInterface)
     *
     * The original callable must return a valid aggregation connection instance
     * of type Predis\Connection\AggregateConnectionInterface, this is enforced
     * by the wrapper returned by this method and an exception is thrown when
     * invalid values are returned.
     *
     * @param OptionsInterface $options  Client options
     * @param callable         $callable Callable initializer
     *
     * @return callable
     * @throws InvalidArgumentException
     */
    protected function getConnectionInitializer(OptionsInterface $options, callable $callable)
    {
        return function ($parameters = null, $autoaggregate = false) use ($callable, $options) {
            $connection = call_user_func_array($callable, [&$parameters, $options, $this]);

            if (!$connection instanceof AggregateConnectionInterface) {
                throw new InvalidArgumentException(sprintf(
                    '%s expects the supplied callable to return an instance of %s, but %s was returned',
                    static::class,
                    AggregateConnectionInterface::class,
                    is_object($connection) ? get_class($connection) : gettype($connection)
                ));
            }

            if ($parameters && $autoaggregate) {
                static::aggregate($options, $connection, $parameters);
            }

            return $connection;
        };
    }

    /**
     * Adds single connections to an aggregate connection instance.
     *
     * @param OptionsInterface             $options    Client options
     * @param AggregateConnectionInterface $connection Target aggregate connection
     * @param array                        $nodes      List of nodes to be added to the target aggregate connection
     */
    public static function aggregate(OptionsInterface $options, AggregateConnectionInterface $connection, array $nodes)
    {
        $connections = $options->connections;

        foreach ($nodes as $node) {
            $connection->add($node instanceof NodeConnectionInterface ? $node : $connections->create($node));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return;
    }
}
