<?php

namespace Predis;

class ConnectionFactory implements IConnectionFactory {
    private static $_globalSchemes;
    private $_instanceSchemes = array();

    public function __construct(Array $schemesMap = null) {
        self::ensureDefaultSchemes();
        if (isset($schemesMap)) {
            foreach ($schemesMap as $connectionClass) {
                self::checkConnectionClass($connectionClass);
            }
            $this->_instanceSchemes = $schemesMap;
        }
    }

    private static function ensureDefaultSchemes() {
        if (!isset(self::$_globalSchemes)) {
            self::$_globalSchemes = array(
                'tcp'   => '\Predis\Network\StreamConnection',
                'unix'  => '\Predis\Network\StreamConnection',
            );
        }
    }

    private static function checkConnectionClass($class) {
        $connectionReflection = new \ReflectionClass($class);
        if (!$connectionReflection->isSubclassOf('\Predis\Network\IConnectionSingle')) {
            throw new ClientException(
                "The class '$class' is not a valid connection class"
            );
        }
    }

    public static function define($scheme, $connectionClass) {
        self::ensureDefaultSchemes();
        self::checkConnectionClass($connectionClass);
        self::$_globalSchemes[$scheme] = $connectionClass;
    }

    public function create($parameters) {
        if (!$parameters instanceof IConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }

        $scheme = $parameters->scheme;
        if (isset($this->_instanceSchemes[$scheme])) {
            $connection = $this->_instanceSchemes[$scheme];
        }
        else if (isset(self::$_globalSchemes[$scheme])) {
            $connection = self::$_globalSchemes[$scheme];
        }
        else {
            throw new ClientException("Unknown connection scheme: $scheme");
        }

        return new $connection($parameters);
    }

    public function createByScheme($scheme, $parameters = array()) {
        if ($parameters instanceof IConnectionParameters) {
            $parameters = $parameters->toArray();
        }
        if (is_array($parameters)) {
            $parameters['scheme'] = $scheme;
            return $this->create($parameters);
        }
        throw new \InvalidArgumentException("Invalid type for connection parameters");
    }
}
