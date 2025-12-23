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

namespace Predis\Pipeline;

use Predis\CommunicationException;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\ConnectionInterface;
use SplQueue;
use Throwable;

/**
 * Command pipeline that writes commands to the servers but discards responses.
 */
class FireAndForget extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        $retry = $connection->getParameters()->retry;

        $retry->callWithRetry(function () use ($connection, $commands) {
            if ($connection instanceof AggregateConnectionInterface) {
                $this->writeToMultiNode($connection, $commands);
            } else {
                $this->writeToSingleNode($connection, $commands);
            }
        }, function (Throwable $e) {
            if ($e instanceof CommunicationException) {
                $e->getConnection()->disconnect();
            }
        });

        $connection->disconnect();

        return [];
    }
}
