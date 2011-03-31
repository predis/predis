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
        return isset($this->_parameters[$parameter]);
    }

    public function __toString() {
        $str = null;
        if ($this->scheme === 'unix') {
            $str = "{$this->scheme}://{$this->path}";
        }
        else {
            $str = "{$this->scheme}://{$this->host}:{$this->port}";
        }

        $query = array();
        $reject = array('scheme', 'host', 'port', 'password', 'path');
        foreach ($this->_userDefined as $k) {
            if (in_array($k, $reject) || !isset($this->_parameters[$k])) {
                continue;
            }
            $v = $this->_parameters[$k];
            $query[] = $k . '=' . ($v === false ? '0' : $v);
        }
        return count($query) > 0 ? ($str . '/?' . implode('&', $query)) : $str;
    }

    public function toArray() {
        return $this->_parameters;
    }
}
