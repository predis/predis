<?php

namespace Predis;

use Predis\Network\IConnectionSingle;

class ConnectionFactory implements IConnectionFactory {
    private static $_globalSchemes;
    private $_instanceSchemes = array();

    public function __construct(Array $schemesMap = null) {
        $this->_instanceSchemes = self::ensureDefaultSchemes();
        if (isset($schemesMap)) {
            foreach ($schemesMap as $scheme => $initializer) {
                $this->defineConnection($scheme, $initializer);
            }
        }
    }

    private static function checkConnectionInitializer($initializer) {
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

    private static function ensureDefaultSchemes() {
        if (!isset(self::$_globalSchemes)) {
            self::$_globalSchemes = array(
                'tcp'   => '\Predis\Network\StreamConnection',
                'unix'  => '\Predis\Network\StreamConnection',
            );
        }
        return self::$_globalSchemes;
    }

    public static function define($scheme, $connectionInitializer) {
        self::ensureDefaultSchemes();
        self::checkConnectionInitializer($connectionInitializer);
        self::$_globalSchemes[$scheme] = $connectionInitializer;
    }

    public function defineConnection($scheme, $connectionInitializer) {
        self::checkConnectionInitializer($connectionInitializer);
        $this->_instanceSchemes[$scheme] = $connectionInitializer;
    }

    public function create($parameters) {
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
        if ($connection instanceof IConnectionSingle) {
            return $connection;
        }
        else {
            throw new \InvalidArgumentException(
                'Objects returned by connection initializers must implement ' .
                'the Predis\Network\IConnectionSingle interface'
            );
        }
    }
}
