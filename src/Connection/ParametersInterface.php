<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

/**
 * Interface defining a container for connection parameters.
 *
 * The actual list of connection parameters depends on the features supported by
 * each connection backend class (please refer to their specific documentation),
 * but the most common parameters used through the library are:
 *
 * @property string $scheme             Connection scheme, such as 'tcp' or 'unix'.
 * @property string $host               IP address or hostname of Redis.
 * @property int    $port               TCP port on which Redis is listening to.
 * @property string $path               Path of a UNIX domain socket file.
 * @property string $alias              Alias for the connection.
 * @property float  $timeout            Timeout for the connect() operation.
 * @property float  $read_write_timeout Timeout for read() and write() operations.
 * @property bool   $async_connect      Performs the connect() operation asynchronously.
 * @property bool   $tcp_nodelay        Toggles the Nagle's algorithm for coalescing.
 * @property bool   $persistent         Leaves the connection open after a GC collection.
 * @property string $password           Password to access Redis (see the AUTH command).
 * @property string $database           Database index (see the SELECT command).
 */
interface ParametersInterface
{
    /**
     * Checks if the specified parameters is set.
     *
     * @param string $parameter Name of the parameter.
     *
     * @return bool
     */
    public function __isset($parameter);

    /**
     * Returns the value of the specified parameter.
     *
     * @param string $parameter Name of the parameter.
     *
     * @return mixed|null
     */
    public function __get($parameter);

    /**
     * Returns basic connection parameters as a valid URI string.
     *
     * @return string
     */
    public function __toString();

    /**
     * Returns an array representation of the connection parameters.
     *
     * @return array
     */
    public function toArray();
}
