<?php

namespace Predis;

use Predis\IConnectionParameters;

class ConnectionParameters implements IConnectionParameters {
    private static $_defaultParameters;
    private static $_validators;

    private $_parameters;
    private $_userDefined;

    public function __construct($parameters = array()) {
        self::ensureDefaults();
        $extractor = is_array($parameters) ? 'filter' : 'parseURI';
        $parameters = $this->$extractor($parameters);
        $this->_userDefined = array_keys($parameters);
        $this->_parameters = array_merge(self::$_defaultParameters, $parameters);
    }

    private static function ensureDefaults() {
        if (!isset(self::$_defaultParameters)) {
            self::$_defaultParameters = array(
                'scheme' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => null,
                'password' => null,
                'connection_async' => false,
                'connection_persistent' => false,
                'connection_timeout' => 5.0,
                'read_write_timeout' => null,
                'alias' => null,
                'weight' => null,
                'path' => null,
                'iterable_multibulk' => false,
                'throw_errors' => true,
            );
        }
        if (!isset(self::$_validators)) {
            $boolValidator = function($value) { return (bool) $value; };
            $floatValidator = function($value) { return (float) $value; };
            $intValidator = function($value) { return (int) $value; };

            self::$_validators = array(
                'port' => $intValidator,
                'connection_async' => $boolValidator,
                'connection_persistent' => $boolValidator,
                'connection_timeout' => $floatValidator,
                'read_write_timeout' => $floatValidator,
                'iterable_multibulk' => $boolValidator,
                'throw_errors' => $boolValidator,
            );
        }
    }

    private function parseURI($uri) {
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }
        if (($parsed = @parse_url($uri)) === false || !isset($parsed['host'])) {
            throw new ClientException("Invalid URI: $uri");
        }
        if (isset($parsed['query'])) {
            foreach (explode('&', $parsed['query']) as $kv) {
                @list($k, $v) = explode('=', $kv);
                $parsed[$k] = $v;
            }
            unset($parsed['query']);
        }
        return $this->filter($parsed);
    }

    private function filter(Array $parameters) {
        if (count($parameters) > 0) {
            $validators = self::$_validators;
            foreach ($parameters as $parameter => $value) {
                if (isset($validators[$parameter])) {
                    $parameters[$parameter] = $validators[$parameter]($value);
                }
            }
        }
        return $parameters;
    }

    public function __get($parameter) {
        return $this->_parameters[$parameter];
    }

    public function __isset($parameter) {
        return in_array($parameter, $this->_userDefined);
    }

    protected function getBaseURI() {
        if ($this->scheme === 'unix') {
            return "{$this->scheme}://{$this->path}";
        }
        return "{$this->scheme}://{$this->host}:{$this->port}";
    }

    protected function getDisallowedURIParts() {
        return array('scheme', 'host', 'port', 'password', 'path');
    }

    public function toArray() {
        return $this->_parameters;
    }

    public function __toString() {
        $query = array();
        $parameters = $this->toArray();
        $reject = $this->getDisallowedURIParts();
        foreach ($this->_userDefined as $param) {
            if (in_array($param, $reject) || !isset($parameters[$param])) {
                continue;
            }
            $value = $parameters[$param];
            $query[] = "$param=" . ($value === false ? '0' : $value);
        }
        if (count($query) === 0) {
            return $this->getBaseURI();
        }
        return $this->getBaseURI() . '/?' . implode('&', $query);
    }
}
