<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use SplQueue;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\SingleConnectionInterface;
use Predis\Profile;
use Predis\Response;

/**
 * Command pipeline wrapped into a MULTI / EXEC transaction.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Atomic extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client)
    {
        if (!$client->getProfile()->supportsCommands(array('multi', 'exec', 'discard'))) {
            throw new ClientException(
                'The specified server profile must support MULTI, EXEC and DISCARD.'
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

        if (!$connection instanceof SingleConnectionInterface) {
            $class = __CLASS__;

            throw new ClientException(
                "$class can be used only with connections to single nodes"
            );
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        $profile = $this->getClient()->getProfile();
        $connection->executeCommand($profile->createCommand('multi'));

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        foreach ($commands as $command) {
            $response = $connection->readResponse($command);

            if ($response instanceof Response\ErrorInterface) {
                $connection->executeCommand($profile->createCommand('discard'));
                throw new Response\ServerException($response->getMessage());
            }
        }

        $executed = $connection->executeCommand($profile->createCommand('exec'));

        if (!isset($executed)) {
            // TODO: should be throwing a more appropriate exception.
            throw new ClientException(
                'The underlying transaction has been aborted by the server'
            );
        }

        if (count($executed) !== count($commands)) {
            throw new ClientException(
                "Invalid number of replies [expected: ".count($commands)." - actual: ".count($executed)."]"
            );
        }

        $responses  = array();
        $sizeOfPipe = count($commands);
        $exceptions = $this->throwServerExceptions();

        for ($i = 0; $i < $sizeOfPipe; $i++) {
            $command  = $commands->dequeue();
            $response = $executed[$i];

            if (!$response instanceof Response\ObjectInterface) {
                $responses[] = $command->parseResponse($response);
            } else if ($response instanceof Response\ErrorInterface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }

            unset($executed[$i]);
        }

        return $responses;
    }
}
