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

/**
 * Defines a connection to communicate with a single Redis server that leverages
 * an external protocol processor to handle pluggable protocol handlers.
 */
interface CompositeConnectionInterface extends NodeConnectionInterface
{
    /**
     * Returns the protocol processor used by the connection.
     */
    public function getProtocol();

    /**
     * Writes the buffer containing over the connection.
     *
     * @param string $buffer String buffer to be sent over the connection.
     */
    public function writeBuffer($buffer);

    /**
     * Reads the given number of bytes from the connection.
     *
     * @param int $length Number of bytes to read from the connection.
     *
     * @return string
     */
    public function readBuffer($length);

    /**
     * Reads a line from the connection.
     *
     * @return string
     */
    public function readLine();
}
