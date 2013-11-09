<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol;

use Predis\Command\CommandInterface;
use Predis\Connection\ComposableConnectionInterface;

/**
 * Defines a pluggable protocol processor capable of serializing commands and
 * deserializing responses into PHP objects directly from a connection.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ProtocolProcessorInterface
{
    /**
     * Writes a command to the specified connection.
     *
     * @param ComposableConnectionInterface $connection Connection to Redis.
     * @param CommandInterface $command Redis command.
     */
    public function write(ComposableConnectionInterface $connection, CommandInterface $command);

    /**
     * Reads a response from the specified connection.
     *
     * @param ComposableConnectionInterface $connection Connection to Redis.
     * @return mixed
     */
    public function read(ComposableConnectionInterface $connection);
}
