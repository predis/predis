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

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use Predis\Transaction\AbortedMultiExecException;
use Predis\Transaction\MultiExec;

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
                "The current profile does not support 'MULTI', 'EXEC' and 'DISCARD'."
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
    protected function executePipeline(ConnectionInterface $connection, \SplQueue $commands)
    {
        $profile = $this->getClient()->getProfile();
        $multi = $profile->createCommand('MULTI');
        $exec = $profile->createCommand('EXEC');

        // open transaction: MULTI
        $connection->writeRequest($multi);

        // transmit all commands in transaction
        foreach ($commands as $command) {
            $connection->writeRequest($command);
        }

        // execute transaction: EXEC
        $connection->writeRequest($exec);

        // response to MULTI
        $response = $connection->readResponse($multi);
        if ($response instanceof ErrorResponseInterface) {
            $this->exception($connection, $response); // close connection and throw ServerException
        }

        // responses to commands (all should be QUEUED result)
        $ex = null;
        foreach ($commands as $command) {
            $response = $connection->readResponse($command);

            // throw first error as a ServerException
            if (($response instanceof ErrorResponseInterface) && !$ex) {
                $ex = new ServerException($response->getMessage());
            }
        }

        // response to EXEC
        $responses = $connection->readResponse($exec);

        // if one of the commands returned an error, only throw after all responses have been received
        if ($ex) {
            throw $ex;
        }

        // if EXEC returned null, the transaction was aborted
        if (!isset($responses)) {
            // fake a MultiExec, which is not used in an atomic pipeline
            $multi_exec = new MultiExec($this->getClient());
            throw new AbortedMultiExecException($multi_exec, 'The underlying transaction has been aborted by the server.');
        }

        // if EXEC returned an error, throw ServerException (but don't close connection, all responses
        // have been read and are accounted for)
        if ($responses instanceof ErrorResponseInterface) {
            throw new ServerException($responses->getMessage());
        }

        if (count($responses) !== count($commands)) {
            $expected = count($commands);
            $received = count($responses);

            throw new ClientException(
                "Invalid number of responses [expected $expected, received $received]."
            );
        }

        $exceptions = $this->throwServerExceptions();

        // parse all unparsed results
        $i = 0;
        foreach ($commands as $command) {
            $response = $responses[$i];

            if (!$response instanceof ResponseInterface) {
                $responses[$i] = $command->parseResponse($response);
            } elseif ($response instanceof ErrorResponseInterface && $exceptions) {
                $this->exception($connection, $response);
            }


            $i++;
        }

        return $responses;
    }
}
