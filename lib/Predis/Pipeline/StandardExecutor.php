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

use Predis\ResponseErrorInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\ReplicationConnectionInterface;
use Predis\ServerException;

/**
 * Implements the standard pipeline executor strategy used
 * to write a list of commands and read their replies over
 * a connection to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StandardExecutor implements PipelineExecutorInterface
{
    /**
     * @param bool $useServerExceptions Specifies if the executor should throw exceptions on server errors.
     */
    public function __construct($useServerExceptions = true)
    {
        $this->useServerExceptions = (bool) $useServerExceptions;
    }

    /**
     * Allows the pipeline executor to perform operations on the
     * connection before starting to execute the commands stored
     * in the pipeline.
     *
     * @param ConnectionInterface Connection instance.
     */
    protected function checkConnection(ConnectionInterface $connection)
    {
        if ($connection instanceof ReplicationConnectionInterface) {
            $connection->switchTo('master');
        }
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param ConnectionInterface $connection The connection that returned the error.
     * @param ResponseErrorInterface $response The error response instance.
     */
    protected function onResponseError(ConnectionInterface $connection, ResponseErrorInterface $response)
    {
        // Force disconnection to prevent protocol desynchronization.
        $connection->disconnect();
        $message = $response->getMessage();

        throw new ServerException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ConnectionInterface $connection, Array &$commands)
    {
        $values = array();
        $sizeofPipe = count($commands);
        $useServerExceptions = $this->useServerExceptions;

        $this->checkConnection($connection);

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        for ($i = 0; $i < $sizeofPipe; $i++) {
            $response = $connection->readResponse($commands[$i]);

            if ($response instanceof ResponseErrorInterface && $useServerExceptions === true) {
                $this->onResponseError($connection, $response);
            }

            $values[] = $response instanceof \Iterator ? iterator_to_array($response) : $response;
            unset($commands[$i]);
        }

        return $values;
    }
}
