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

use Predis\Command\CommandInterface;

/**
 * Defines a connection object used to communicate with one or multiple
 * Redis servers.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ConnectionInterface
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
     * Writes the given command over the connection.
     *
     * @param CommandInterface $command Instance of a Redis command.
     */
    public function writeRequest(CommandInterface $command);

    /**
     * Reads the response to a command from the connection.
     *
     * @param CommandInterface $command Instance of a Redis command.
     * @return mixed
     */
    public function readResponse(CommandInterface $command);

    /**
     * Writes a command over the connection and reads back the response.
     *
     * @param CommandInterface $command Instance of a Redis command.
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);
}
