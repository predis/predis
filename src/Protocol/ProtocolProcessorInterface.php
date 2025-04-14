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

namespace Predis\Protocol;

use Predis\Command\CommandInterface;
use Predis\Connection\CompositeConnectionInterface;

/**
 * Defines a pluggable protocol processor capable of serializing commands and
 * deserializing responses into PHP objects directly from a connection.
 */
interface ProtocolProcessorInterface
{
    /**
     * Writes a request over a connection to Redis.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     * @param CommandInterface             $command    Command instance.
     */
    public function write(CompositeConnectionInterface $connection, CommandInterface $command);

    /**
     * Reads a response from a connection to Redis.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     *
     * @return mixed
     */
    public function read(CompositeConnectionInterface $connection);
}
