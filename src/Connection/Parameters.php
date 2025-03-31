<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
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
 */
class Parameters implements ParametersInterface
{
    protected static $defaults = [
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'protocol' => 2,
    ];

    /**
     * Set of connection parameters already filtered
     * for NULL or 0-length string values.
     *
     * @var array
     */
    protected $parameters;

    /**
     * @param array $parameters Named array of connection parameters.
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $this->filter($parameters + static::$defaults);
    }

    /**
     * Filters parameters removing entries with NULL or 0-length string values.
     *
     * @params array $parameters Array of parameters to be filtered
     *
     * @return array
     */
    protected function filter(array $parameters)
    {
        return array_filter($parameters, function ($value) {
            return $value !== null && $value !== '';
        });
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

        return new static($parameters ?: []);
    }

    /**
     * Parses an URI string returning an array of connection parameters.
     *
     * When using the "redis" and "rediss" schemes the URI is parsed according
     * to the rules defined by the provisional registration documents approved
     * by IANA. If the URI has a password in its "user-information" part or a
     * database number in the "path" part these values override the values of
     * "password" and "database" if they are present in the "query" part.
     *
     * @see http://www.iana.org/assignments/uri-schemes/prov/redis
     * @see http://www.iana.org/assignments/uri-schemes/prov/rediss
     *
     * @param string $uri URI string.
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function parse($uri)
    {
        if (stripos($uri, 'unix://') === 0) {
            // parse_url() can parse unix:/path/to/sock so we do not need the
            // unix:///path/to/sock hack, we will support it anyway until 2.0.
            $uri = str_ireplace('unix://', 'unix:', $uri);
        }

        if (!$parsed = parse_url($uri)) {
            throw new InvalidArgumentException("Invalid parameters URI: $uri");
        }

        if (
            isset($parsed['host'])
            && false !== strpos($parsed['host'], '[')
            && false !== strpos($parsed['host'], ']')
        ) {
            $parsed['host'] = substr($parsed['host'], 1, -1);
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryarray);
            unset($parsed['query']);

            $parsed = array_merge($parsed, $queryarray);
        }

        if (stripos($uri, 'redis') === 0) {
            if (isset($parsed['user'])) {
                if (strlen($parsed['user'])) {
                    $parsed['username'] = $parsed['user'];
                }
                unset($parsed['user']);
            }

            if (isset($parsed['pass'])) {
                if (strlen($parsed['pass'])) {
                    $parsed['password'] = $parsed['pass'];
                }
                unset($parsed['pass']);
            }

            if (isset($parsed['path']) && preg_match('/^\/(\d+)(\/.*)?/', $parsed['path'], $path)) {
                $parsed['database'] = $path[1];

                if (isset($path[2])) {
                    $parsed['path'] = $path[2];
                } else {
                    unset($parsed['path']);
                }
            }
        }

        return $parsed;
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
    public function __get($parameter)
    {
        if (isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }
    }

    public function __set($parameter, $value)
    {
        $this->parameters[$parameter] = $value;
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
    public function __toString()
    {
        if ($this->scheme === 'unix') {
            return "$this->scheme:$this->path";
        }

        if (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return "$this->scheme://[$this->host]:$this->port";
        }

        return "$this->scheme://$this->host:$this->port";
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return ['parameters'];
    }
}
