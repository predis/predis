<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Predis\Command\CommandInterface;

/**
 * Defines a connection used to communicate with a single Redis node.
 */
interface NodeConnectionInterface extends ConnectionInterface
{
    /**
     * Returns a string representation of the connection.
     *
     * @return string
     */
    public function __toString();

    /**
     * Returns the underlying resource used to communicate with Redis.
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Returns the parameters used to initialize the connection.
     *
     * @return ParametersInterface
     */
    public function getParameters();

    /**
     * Pushes the given command into a queue of commands executed when
     * establishing the actual connection to Redis.
     *
     * @param CommandInterface $command Instance of a Redis command.
     */
    public function addConnectCommand(CommandInterface $command);

    /**
     * Reads a response from the server.
     *
     * @return mixed
     */
    public function read();
}
