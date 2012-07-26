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
use Predis\ResponseErrorInterface;
use Predis\ServerException;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\ReplicationConnectionInterface;

/**
 * Implements the standard pipeline executor strategy used
 * to write a list of commands and read their replies over
 * a connection to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StandardExecutor implements PipelineExecutorInterface
{
    protected $exceptions;

    /**
     * @param bool $exceptions Specifies if the executor should throw exceptions on server errors.
     */
    public function __construct($exceptions = true)
    {
        $this->exceptions = (bool) $exceptions;
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
    public function execute(ConnectionInterface $connection, SplQueue $commands)
    {
        $size = count($commands);
        $values = array();
        $exceptions = $this->exceptions;

        $this->checkConnection($connection);

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        for ($i = 0; $i < $size; $i++) {
            $response = $connection->readResponse($commands->dequeue());

            if ($response instanceof ResponseErrorInterface && $exceptions === true) {
                $this->onResponseError($connection, $response);
            }

            $values[$i] = $response instanceof \Iterator ? iterator_to_array($response) : $response;
        }

        return $values;
    }
}
