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

use Predis\Commands\ICommand;

/**
 * Defines a connection object used to communicate with one or multiple
 * Redis servers.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnection
{
    /**
     * Opens the connection.
     */
    public function connect();

    /**
     * Closes the connection.
     */
    public function disconnect();

    /**
     * Returns if the connection is open.
     *
     * @return Boolean
     */
    public function isConnected();

    /**
     * Write a Redis command on the connection.
     *
     * @param ICommand $command Instance of a Redis command.
     */
    public function writeCommand(ICommand $command);

    /**
     * Reads the reply for a Redis command from the connection.
     *
     * @param ICommand $command Instance of a Redis command.
     * @return mixed
     */
    public function readResponse(ICommand $command);

    /**
     * Writes a Redis command to the connection and reads back the reply.
     *
     * @param ICommand $command Instance of a Redis command.
     * @return mixed
     */
    public function executeCommand(ICommand $command);
}
