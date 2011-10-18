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

use Predis\Network\IConnectionSingle;

/**
 * Provides a default factory for Redis connections that maps URI schemes
 * to connection classes implementing the Predis\Network\IConnectionSingle
 * interface.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionFactory implements IConnectionFactory
{
    private static $_globalSchemes;

    private $_instanceSchemes = array();

    /**
     * @param array $schemesMap Map of URI schemes to connection classes.
     */
    public function __construct(Array $schemesMap = null)
    {
        $this->_instanceSchemes = self::ensureDefaultSchemes();

        if (isset($schemesMap)) {
            foreach ($schemesMap as $scheme => $initializer) {
                $this->defineConnection($scheme, $initializer);
            }
        }
    }

    /**
     * Checks if the provided argument represents a valid connection class
     * implementing the Predis\Network\IConnectionSingle interface. Optionally,
     * callable objects are used for lazy initialization of connection objects.
     *
     * @param mixed $initializer FQN of a connection class or a callable for lazy initialization.
     */
    private static function checkConnectionInitializer($initializer)
    {
        if (is_callable($initializer)) {
            return;
        }

        $initializerReflection = new \ReflectionClass($initializer);

        if (!$initializerReflection->isSubclassOf('\Predis\Network\IConnectionSingle')) {
            throw new \InvalidArgumentException(
                'A connection initializer must be a valid connection class or a callable object'
            );
        }
    }

    /**
     * Ensures that the default global URI schemes map is initialized.
     *
     * @return array
     */
    private static function ensureDefaultSchemes()
    {
        if (!isset(self::$_globalSchemes)) {
            self::$_globalSchemes = array(
                'tcp'   => '\Predis\Network\StreamConnection',
                'unix'  => '\Predis\Network\StreamConnection',
            );
        }

        return self::$_globalSchemes;
    }

    /**
     * Defines a new URI scheme => connection class relation at class level.
     *
     * @param string $scheme URI scheme
     * @param mixed $connectionInitializer FQN of a connection class or a callable for lazy initialization.
     */
    public static function define($scheme, $connectionInitializer)
    {
        self::ensureDefaultSchemes();
        self::checkConnectionInitializer($connectionInitializer);
        self::$_globalSchemes[$scheme] = $connectionInitializer;
    }

    /**
     * Defines a new URI scheme => connection class relation at instance level.
     *
     * @param string $scheme URI scheme
     * @param mixed $connectionInitializer FQN of a connection class or a callable for lazy initialization.
     */
    public function defineConnection($scheme, $connectionInitializer)
    {
        self::checkConnectionInitializer($connectionInitializer);
        $this->_instanceSchemes[$scheme] = $connectionInitializer;
    }

    /**
     * {@inheritdoc}
     */
    public function create($parameters)
    {
        if (!$parameters instanceof IConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }

        $scheme = $parameters->scheme;
        if (!isset($this->_instanceSchemes[$scheme])) {
            throw new \InvalidArgumentException("Unknown connection scheme: $scheme");
        }

        $initializer = $this->_instanceSchemes[$scheme];
        if (!is_callable($initializer)) {
            return new $initializer($parameters);
        }

        $connection = call_user_func($initializer, $parameters);
        if (!$connection instanceof IConnectionSingle) {
            throw new \InvalidArgumentException(
                'Objects returned by connection initializers must implement ' .
                'the Predis\Network\IConnectionSingle interface'
            );
        }

        return $connection;
    }
}
