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

use Predis\Command\Command;
use Predis\Command\CommandInterface;

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
        $offset = 0;
        $length = strlen($buffer);

        while ($offset < $length) {
            $start = $offset;
            $lineEnd = strpos($buffer, "\r\n", $offset);
            $argsCount = (int) substr($buffer, $offset + 1, $lineEnd - $offset - 1);
            $offset = $lineEnd + 2;

            // Advance by each bulk string's own declared byte length rather than
            // splitting on literal "\r\n", which a bulk string's value may legitimately
            // contain (see GHSA-w6f5-v2h6-g786).
            for ($i = 0; $i < $argsCount; ++$i) {
                $lineEnd = strpos($buffer, "\r\n", $offset);
                $bulkLen = (int) substr($buffer, $offset + 1, $lineEnd - $offset - 1);
                $offset = $lineEnd + 2 + $bulkLen + 2;
            }

            $command = substr($buffer, $start, $offset - $start);
            $commandObj = Command::deserializeCommand($command);
            $this->getConnectionByCommand($commandObj)->write($command);
        }
    }
}
