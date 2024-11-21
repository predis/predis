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
use Predis\Connection\Factory;
use Predis\Connection\FactoryInterface;
use Predis\Connection\PhpiredisSocketConnection;
use Predis\Connection\PhpiredisStreamConnection;
use Predis\Connection\RelayConnection;

/**
 * Configures a new connection factory instance.
 *
 * The client uses the connection factory to create the underlying connections
 * to single redis nodes in a single-server configuration or in replication and
 * cluster configurations.
 */
class Connections implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }

        if ($value instanceof FactoryInterface) {
            return $value;
        } elseif (is_array($value)) {
            return $this->createFactoryByArray($options, $value);
        } elseif (is_string($value)) {
            return $this->createFactoryByString($options, $value);
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s expects a valid connection factory', static::class
            ));
        }
    }

    /**
     * Creates a new connection factory from a named array.
     *
     * The factory instance is configured according to the supplied named array
     * mapping URI schemes (passed as keys) to the FCQN of classes implementing
     * Predis\Connection\NodeConnectionInterface, or callable objects acting as
     * lazy initializers and returning new instances of classes implementing
     * Predis\Connection\NodeConnectionInterface.
     *
     * @param OptionsInterface $options Client options
     * @param array            $value   Named array mapping URI schemes to classes or callables
     *
     * @return FactoryInterface
     */
    protected function createFactoryByArray(OptionsInterface $options, array $value)
    {
        /**
         * @var FactoryInterface
         */
        $factory = $this->getDefault($options);

        foreach ($value as $scheme => $initializer) {
            $factory->define($scheme, $initializer);
        }

        return $factory;
    }

    /**
     * Creates a new connection factory from a descriptive string.
     *
     * The factory instance is configured according to the supplied descriptive
     * string that identifies specific configurations of schemes and connection
     * classes. Supported configuration values are:
     *
     * - "phpiredis-stream" maps tcp, redis, unix to PhpiredisStreamConnection
     * - "phpiredis-socket" maps tcp, redis, unix to PhpiredisSocketConnection
     * - "phpiredis" is an alias of "phpiredis-stream"
     * - "relay" maps tcp, redis, unix, tls, rediss to RelayConnection
     *
     * @param OptionsInterface $options Client options
     * @param string           $value   Descriptive string identifying the desired configuration
     *
     * @return FactoryInterface
     */
    protected function createFactoryByString(OptionsInterface $options, string $value)
    {
        /**
         * @var FactoryInterface
         */
        $factory = $this->getDefault($options);

        switch (strtolower($value)) {
            case 'phpiredis':
            case 'phpiredis-stream':
                $factory->define('tcp', PhpiredisStreamConnection::class);
                $factory->define('redis', PhpiredisStreamConnection::class);
                $factory->define('unix', PhpiredisStreamConnection::class);
                break;

            case 'phpiredis-socket':
                $factory->define('tcp', PhpiredisSocketConnection::class);
                $factory->define('redis', PhpiredisSocketConnection::class);
                $factory->define('unix', PhpiredisSocketConnection::class);
                break;

            case 'relay':
                $factory->define('tcp', RelayConnection::class);
                $factory->define('redis', RelayConnection::class);
                $factory->define('unix', RelayConnection::class);
                break;

            case 'default':
                return $factory;

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s does not recognize `%s` as a supported configuration string', static::class, $value
                ));
        }

        return $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $factory = new Factory();

        if ($options->defined('parameters')) {
            $factory->setDefaultParameters($options->parameters);
        }

        return $factory;
    }
}
