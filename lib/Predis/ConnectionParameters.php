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

use Predis\Option\OptionInterface;

/**
 * Handles parsing and validation of connection parameters.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionParameters implements ConnectionParametersInterface
{
    private $parameters;

    private static $defaults = array(
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 5.0,
        'iterable_multibulk' => false,
        'throw_errors' => true,
    );

    /**
     * @param string|array Connection parameters in the form of an URI string or a named array.
     */
    public function __construct($parameters = array())
    {
        if (!is_array($parameters)) {
            $parameters = $this->parseURI($parameters);
        }

        $this->parameters = $this->filter($parameters) + $this->getDefaults();
    }

    /**
     * Returns some default parameters with their values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return self::$defaults;
    }

    /**
     * Returns validators functions for the values of certain parameters.
     *
     * @return array
     */
    protected function getValidators()
    {
        $bool = function($value) { return (bool) $value; };
        $float = function($value) { return (float) $value; };
        $int = function($value) { return (int) $value; };

        return array(
            'port' => $int,
            'async_connect' => $bool,
            'persistent' => $bool,
            'timeout' => $float,
            'read_write_timeout' => $float,
            'iterable_multibulk' => $bool,
            'throw_errors' => $bool,
        );
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
            $validators = array_intersect_key($this->getValidators(), $parameters);
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
        if (isset($this->{$parameter})) {
            return $this->parameters[$parameter];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($parameter)
    {
        return isset($this->parameters[$parameter]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters');
    }
}
