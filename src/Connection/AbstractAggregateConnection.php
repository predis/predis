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
        $rawCommands = [];
        $explodedBuffer = explode("\r\n", trim($buffer));

        while (!empty($explodedBuffer)) {
            $argsLen = (int) explode('*', $explodedBuffer[0])[1];
            $cmdLen = ($argsLen * 2) + 1;
            $rawCommands[] = array_splice($explodedBuffer, 0, $cmdLen);
        }

        foreach ($rawCommands as $command) {
            $command = implode("\r\n", $command) . "\r\n";
            $commandObj = Command::deserializeCommand($command);
            $this->getConnectionByCommand($commandObj)->write($command);
        }
    }
}
