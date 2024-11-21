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

namespace Predis\Pipeline;

use Predis\Connection\ConnectionInterface;
use Predis\Response\Error;
use Predis\Response\ServerException;
use Relay\Exception as RelayException;
use SplQueue;

class RelayAtomic extends Atomic
{
    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        /** @var \Predis\Connection\RelayConnection $connection */
        $client = $connection->getClient();

        $throw = $this->client->getOptions()->exceptions;

        try {
            $transaction = $client->multi();

            foreach ($commands as $command) {
                $name = $command->getId();

                in_array($name, $connection->atypicalCommands)
                    ? $transaction->{$name}(...$command->getArguments())
                    : $transaction->rawCommand($name, ...$command->getArguments());
            }

            $responses = $transaction->exec();

            if (!is_array($responses)) {
                return $responses;
            }

            foreach ($responses as $key => $response) {
                if ($response instanceof RelayException) {
                    if ($throw) {
                        throw $response;
                    }

                    $responses[$key] = new Error($response->getMessage());
                }
            }

            return $responses;
        } catch (RelayException $ex) {
            if ($client->getMode() !== $client::ATOMIC) {
                $client->discard();
            }

            throw new ServerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}
