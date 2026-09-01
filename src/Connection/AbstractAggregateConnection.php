<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Predis\Command\CommandInterface;
use Predis\NotSupportedException;

abstract class AbstractAggregateConnection implements AggregateConnectionInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function add(NodeConnectionInterface $connection);

    /**
     * {@inheritDoc}
     */
    abstract public function remove(NodeConnectionInterface $connection);

    /**
     * {@inheritDoc}
     */
    abstract public function getConnectionByCommand(CommandInterface $command);

    /**
     * {@inheritDoc}
     */
    abstract public function getConnectionById($connectionID);

    /**
     * {@inheritDoc}
     */
    abstract public function connect();

    /**
     * {@inheritDoc}
     */
    abstract public function disconnect();

    /**
     * {@inheritDoc}
     */
    abstract public function isConnected();

    /**
     * {@inheritDoc}
     */
    abstract public function writeRequest(CommandInterface $command);

    /**
     * {@inheritDoc}
     */
    abstract public function readResponse(CommandInterface $command);

    /**
     * {@inheritDoc}
     */
    abstract public function executeCommand(CommandInterface $command);

    /**
     * {@inheritDoc}
     */
    abstract public function getParameters();

    /**
     * {@inheritDoc}
     */
    public function write(string $buffer): void
    {
        // Refuse raw buffers: re-splitting them on "\r\n" ignored RESP length
        // prefixes and let CRLF-smuggled commands be routed to a node
        // (CVE GHSA-w6f5-v2h6-g786). Pipelines write each command individually.
        throw new NotSupportedException(
            'Aggregate connections cannot write a raw command buffer; '
            . 'route each command through writeRequest() instead.'
        );
    }
}
