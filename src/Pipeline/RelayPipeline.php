<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Connection\ConnectionInterface;
use Predis\Connection\RelayConnection;
use Predis\Response\ServerException;
use Relay\Exception as RelayException;
use SplQueue;

class RelayPipeline extends Pipeline
{
    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param  RelayConnection $connection Current connection instance.
     * @param  SplQueue        $commands   Queued commands.
     * @return array
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        try {
            $pipeline = $connection->getClient()->pipeline();

            foreach ($commands as $command) {
                $name = $command->getId();

                in_array($name, $connection->atypicalCommands)
                    ? $pipeline->{$name}(...$command->getArguments())
                    : $pipeline->rawCommand($name, ...$command->getArguments());
            }

            return $pipeline->exec();
        } catch (RelayException $ex) {
            $connection->getClient()->discard();

            throw new ServerException($ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        }
    }
}
