<?php

namespace Predis;

class ConnectionFactory implements IConnectionFactory {
    private static $_globalSchemes;
    private $_instanceSchemes = array();

    public function __construct(Array $schemesMap = null) {
        $this->_instanceSchemes = self::ensureDefaultSchemes();
        if (isset($schemesMap)) {
            foreach ($schemesMap as $scheme => $connectionClass) {
                $this->defineConnection($scheme, $connectionClass);
            }
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

    private static function ensureDefaultSchemes() {
        if (!isset(self::$_globalSchemes)) {
            self::$_globalSchemes = array(
                'tcp'   => '\Predis\Network\StreamConnection',
                'unix'  => '\Predis\Network\StreamConnection',
            );
        }
        return self::$_globalSchemes;
    }

    public static function define($scheme, $connectionClass) {
        self::ensureDefaultSchemes();
        self::checkConnectionClass($connectionClass);
        self::$_globalSchemes[$scheme] = $connectionClass;
    }

    public function defineConnection($scheme, $connectionClass) {
        self::checkConnectionClass($connectionClass);
        $this->_instanceSchemes[$scheme] = $connectionClass;
    }

    public function create($parameters) {
        if (!$parameters instanceof IConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }
        $scheme = $parameters->scheme;
        if (!isset($this->_instanceSchemes[$scheme])) {
            throw new ClientException("Unknown connection scheme: $scheme");
        }
        $connection = $this->_instanceSchemes[$scheme];
        return new $connection($parameters);
    }
}
