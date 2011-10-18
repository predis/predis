<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Protocol\IProtocolProcessor;

/**
 * Defines a connection object used to communicate with a single Redis server
 * that leverages an external protocol processor to handle pluggable protocol
 * handlers.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnectionComposable extends IConnectionSingle
{
    /**
     * Sets the protocol processor used by the connection.
     *
     * @param IProtocolProcessor $protocol Protocol processor.
     */
    public function setProtocol(IProtocolProcessor $protocol);

    /**
     * Gets the protocol processor used by the connection.
     */
    public function getProtocol();

    /**
     * Writes a buffer that contains a serialized Redis command.
     *
     * @param string $buffer Serialized Redis command.
     */
    public function writeBytes($buffer);

    /**
     * Reads a specified number of bytes from the connection.
     *
     * @param string
     */
    public function readBytes($length);

    /**
     * Reads a line from the connection.
     *
     * @param string
     */
    public function readLine();
}
