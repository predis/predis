<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
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
use Predis\Connection\RelayConnection;
use Predis\Connection\RelayFactory;

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
        }
        throw new InvalidArgumentException(sprintf(
            '%s expects a valid connection factory', static::class
        ));
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
     * - "relay" maps tcp, redis, unix, tls, rediss to RelayConnection
     *
     * @param OptionsInterface $options Client options
     * @param string           $value   Descriptive string identifying the desired configuration
     *
     * @return FactoryInterface
     */
    protected function createFactoryByString(OptionsInterface $options, string $value)
    {
        switch (strtolower($value)) {
            case 'relay':
                return $this->getRelayFactory($options);

            case 'default':
                return $this->getDefault($options);

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s does not recognize `%s` as a supported configuration string', static::class, $value
                ));
        }
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

    /**
     * Creates RelayFactory instance.
     *
     * @param  OptionsInterface $options
     * @return FactoryInterface
     */
    private function getRelayFactory(OptionsInterface $options): FactoryInterface
    {
        $factory = new RelayFactory();

        if ($options->defined('parameters')) {
            $factory->setDefaultParameters($options->parameters);
        }

        return $factory;
    }
}
