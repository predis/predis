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

use Predis\IConnectionParameters;
use Predis\Options\IOption;

/**
 * Handles parsing and validation of connection parameters.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionParameters implements IConnectionParameters
{
    private static $_defaultParameters;
    private static $_validators;

    private $_parameters;
    private $_userDefined;

    /**
     * @param string|array Connection parameters in the form of an URI string or a named array.
     */
    public function __construct($parameters = array())
    {
        self::ensureDefaults();

        if (!is_array($parameters)) {
            $parameters = $this->parseURI($parameters);
        }

        $this->_userDefined = array_keys($parameters);
        $this->_parameters = $this->filter($parameters) + self::$_defaultParameters;
    }

    /**
     * Ensures that the default values and validators are initialized.
     */
    private static function ensureDefaults()
    {
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
            $bool = function($value) { return (bool) $value; };
            $float = function($value) { return (float) $value; };
            $int = function($value) { return (int) $value; };

            self::$_validators = array(
                'port' => $int,
                'connection_async' => $bool,
                'connection_persistent' => $bool,
                'connection_timeout' => $float,
                'read_write_timeout' => $float,
                'iterable_multibulk' => $bool,
                'throw_errors' => $bool,
            );
        }
    }

    /**
     * Defines a default value and a validator for the specified parameter.
     *
     * @param string $parameter Name of the parameter.
     * @param mixed $default Default value or an instance of IOption.
     * @param mixed $callable A validator callback.
     */
    public static function define($parameter, $default, $callable = null)
    {
        self::ensureDefaults();
        self::$_defaultParameters[$parameter] = $default;

        if ($default instanceof IOption) {
            self::$_validators[$parameter] = $default;
            return;
        }

        if (!isset($callable)) {
            unset(self::$_validators[$parameter]);
            return;
        }

        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(
                "The validator for $parameter must be a callable object"
            );
        }

        self::$_validators[$parameter] = $callable;
    }

    /**
     * Undefines the default value and validator for the specified parameter.
     *
     * @param string $parameter Name of the parameter.
     */
    public static function undefine($parameter)
    {
        self::ensureDefaults();
        unset(self::$_defaultParameters[$parameter], self::$_validators[$parameter]);
    }

    /**
     * Parses an URI string and returns an array of connection parameters.
     *
     * @param string $uri Connection string.
     * @return array
     */
    private function parseURI($uri)
    {
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

        return $parsed;
    }

    /**
     * Validates and converts each value of the connection parameters array.
     *
     * @param array $parameters Connection parameters.
     * @return array
     */
    private function filter(Array $parameters)
    {
        if (count($parameters) > 0) {
            $validators = array_intersect_key(self::$_validators, $parameters);
            foreach ($validators as $parameter => $validator) {
                $parameters[$parameter] = $validator($parameters[$parameter]);
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($parameter)
    {
        $value = $this->_parameters[$parameter];

        if ($value instanceof IOption) {
            $this->_parameters[$parameter] = ($value = $value->getDefault());
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($parameter)
    {
        return isset($this->_parameters[$parameter]);
    }

    /**
     * Checks if the specified parameter has been set by the user.
     *
     * @param string $parameter Name of the parameter.
     * @return Boolean
     */
    public function isSetByUser($parameter)
    {
        return in_array($parameter, $this->_userDefined);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBaseURI()
    {
        if ($this->scheme === 'unix') {
            return "{$this->scheme}://{$this->path}";
        }

        return "{$this->scheme}://{$this->host}:{$this->port}";
    }

    /**
     * Returns the URI parts that must be omitted when calling __toString().
     *
     * @return array
     */
    protected function getDisallowedURIParts()
    {
        return array('scheme', 'host', 'port', 'password', 'path');
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->_parameters;
    }

    /**
     * Returns a string representation of the parameters.
     *
     * @return string
     */
    public function __toString()
    {
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

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('_parameters', '_userDefined');
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        self::ensureDefaults();
    }
}
