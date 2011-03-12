<?php

namespace Predis;

use Predis\Options\IOption;
use Predis\Options\Option;
use Predis\Options\CustomOption;

class ConnectionParameters {
    private $_parameters;
    private $_userDefined;
    private static $_sharedOptions;

    public function __construct($parameters = array()) {
        $extractor = is_array($parameters) ? 'filter' : 'parseURI';
        $this->_parameters = $this->$extractor($parameters);
        $this->_userDefined = array_fill_keys(array_keys($this->_parameters), true);
    }

    private static function paramsExtractor($params, $kv) {
        @list($k, $v) = explode('=', $kv);
        $params[$k] = $v;
        return $params;
    }

    private static function getSharedOptions() {
        if (isset(self::$_sharedOptions)) {
            return self::$_sharedOptions;
        }

        $optEmpty   = new Option();
        $optBoolFalse = new CustomOption(array(
            'validate' => function($value) { return (bool) $value; },
            'default'  => function() { return false; },
        ));
        $optBoolTrue = new CustomOption(array(
            'validate' => function($value) { return (bool) $value; },
            'default'  => function() { return true; },
        ));

        self::$_sharedOptions = array(
            'scheme' => new CustomOption(array(
                'default'  => function() { return 'tcp'; },
            )),
            'host' => new CustomOption(array(
                'default'  => function() { return '127.0.0.1'; },
            )),
            'port' => new CustomOption(array(
                'validate' => function($value) { return (int) $value; },
                'default'  => function() { return 6379; },
            )),
            'path' => $optEmpty,
            'database' => $optEmpty,
            'password' => $optEmpty,
            'connection_async' => $optBoolFalse,
            'connection_persistent' => $optBoolFalse,
            'connection_timeout' => new CustomOption(array(
                'validate' => function($value) { return (float) $value; },
                'default'  => function() { return 5; },
            )),
            'read_write_timeout' => new CustomOption(array(
                'validate' => function($value) { return (float) $value; },
            )),
            'alias' => $optEmpty,
            'weight' => $optEmpty,
            'iterable_multibulk' => $optBoolFalse,
            'throw_errors' => $optBoolTrue,
        );

        return self::$_sharedOptions;
    }

    public static function define($parameter, IOption $handler) {
        self::getSharedOptions();
        self::$_sharedOptions[$parameter] = $handler;
    }

    public static function undefine($parameter) {
        self::getSharedOptions();
        unset(self::$_sharedOptions[$parameter]);
    }

    protected function parseURI($uri) {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException('URI must be a string');
        }
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }
        $parsed = @parse_url($uri);
        if ($parsed === false || !isset($parsed['host'])) {
            throw new \InvalidArgumentException("Invalid URI: $uri");
        }
        if (array_key_exists('query', $parsed)) {
            $query  = explode('&', $parsed['query']);
            $parsed = array_reduce($query, 'self::paramsExtractor', $parsed);
        }
        unset($parsed['query']);
        return $this->filter($parsed);
    }

    protected function filter(Array $parameters) {
        $handlers = self::getSharedOptions();
        foreach ($parameters as $parameter => $value) {
            if (isset($handlers[$parameter])) {
                $parameters[$parameter] = $handlers[$parameter]($value);
            }
        }
        return $parameters;
    }

    private function tryInitializeValue($parameter) {
        if (isset(self::$_sharedOptions[$parameter])) {
            $value = self::$_sharedOptions[$parameter]->getDefault();
            $this->_parameters[$parameter] = $value;
            return $value;
        }
    }

    public function __get($parameter) {
        if (isset($this->_parameters[$parameter])) {
            return $this->_parameters[$parameter];
        }
        return $this->tryInitializeValue($parameter);
    }

    public function __isset($parameter) {
        return isset($this->_userDefined[$parameter]);
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
        foreach ($this->_parameters as $k => $v) {
            if (in_array($k, $reject) || !isset($this->_userDefined[$k])) {
                continue;
            }
            $query[] = $k . '=' . ($v === false ? '0' : $v);
        }
        return count($query) > 0 ? ($str . '/?' . implode('&', $query)) : $str;
    }

    public function toArray() {
        return $this->_parameters;
    }
}
