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

use SplQueue;
use Predis\Response\ServerException;
use Predis\Connection\ConnectionInterface;

class RelayAtomic extends Atomic
{
    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        try {
            /** @var \Predis\Connection\RelayConnection $connection */
            $transaction = $connection->getClient()->multi();

            foreach ($commands as $command) {
                $name = $command->getId();

                in_array($name, $connection->atypicalCommands)
                    ? $transaction->{$name}(...$command->getArguments())
                    : $transaction->rawCommand($name, ...$command->getArguments());
            }

            return $transaction->exec();
        } catch (RelayException $ex)
            $connection->getClient()->discard();

            throw new ServerException($ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        }
    }
}
