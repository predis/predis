<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use InvalidArgumentException;

/**
 * Container for connection parameters used to initialize connections to Redis.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Parameters implements ParametersInterface
{
    private $parameters;

    private static $defaults = array(
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 5.0,
    );

    /**
     * @param array $parameters Named array of connection parameters.
     */
    public function __construct(array $parameters = array())
    {
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
     * Creates a new instance by supplying the initial parameters either in the
     * form of an URI string or a named array.
     *
     * @param array|string $parameters Set of connection parameters.
     *
     * @return Parameters
     */
    public static function create($parameters)
    {
        if (is_string($parameters)) {
            $parameters = static::parse($parameters);
        }

        return new static($parameters ?: array());
    }

    /**
     * Parses an URI string returning an array of connection parameters.
     *
     * @param string $uri URI string.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public static function parse($uri)
    {
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }

        if (!($parsed = parse_url($uri)) || !isset($parsed['host'])) {
            throw new InvalidArgumentException("Invalid parameters URI: $uri");
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryarray);
            unset($parsed['query']);

            $parsed = array_merge($parsed, $queryarray);
        }

        return $parsed;
    }

    /**
     * Validates and converts each value of the connection parameters array.
     *
     * @param array $parameters Connection parameters.
     *
     * @return array
     */
    protected function filter(array $parameters)
    {
        return $parameters ?: array();
    }

    /**
     * {@inheritdoc}
     */
    public function __get($parameter)
    {
        if (isset($this->parameters[$parameter])) {
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
