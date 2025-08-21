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

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use SplQueue;

/**
 * Command pipeline wrapped into a MULTI / EXEC transaction.
 */
class Atomic extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client)
    {
        if (!$client->getCommandFactory()->supports('multi', 'exec', 'discard')) {
            throw new ClientException(
                "'MULTI', 'EXEC' and 'DISCARD' are not supported by the current command factory."
            );
        }

        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnection()
    {
        $connection = $this->getClient()->getConnection();

        if (!$connection instanceof NodeConnectionInterface) {
            $class = __CLASS__;

            throw new ClientException("The class '$class' does not support aggregate connections.");
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        $commandFactory = $this->getClient()->getCommandFactory();
        $connection->executeCommand($commandFactory->create('multi'));

        if ($connection instanceof AggregateConnectionInterface) {
            $this->writeToMultiNode($connection, $commands);
        } else {
            $this->writeToSingleNode($connection, $commands);
        }

        foreach ($commands as $command) {
            $response = $connection->readResponse($command);

            if ($response instanceof ErrorResponseInterface) {
                $connection->executeCommand($commandFactory->create('discard'));
                throw new ServerException($response->getMessage());
            }
        }

        $executed = $connection->executeCommand($commandFactory->create('exec'));

        if (!isset($executed)) {
            throw new ClientException(
                'The underlying transaction has been aborted by the server.'
            );
        }

        if (count($executed) !== count($commands)) {
            $expected = count($commands);
            $received = count($executed);

            throw new ClientException(
                "Invalid number of responses [expected $expected, received $received]."
            );
        }

        $responses = [];
        $sizeOfPipe = count($commands);
        $exceptions = $this->throwServerExceptions();
        $protocolVersion = (int) $connection->getParameters()->protocol;

        for ($i = 0; $i < $sizeOfPipe; ++$i) {
            $command = $commands->dequeue();
            $response = $executed[$i];

            if (!$response instanceof ResponseInterface) {
                if ($protocolVersion === 2) {
                    $responses[] = $command->parseResponse($response);
                } else {
                    $responses[] = $command->parseResp3Response($response);
                }
            } elseif ($response instanceof ErrorResponseInterface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }

            unset($executed[$i]);
        }

        return $responses;
    }
}
