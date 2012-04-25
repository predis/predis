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

use Predis\ServerException;
use Predis\Network\IConnection;
use Predis\Network\IConnectionReplication;

/**
 * Implements the standard pipeline executor strategy used
 * to write a list of commands and read their replies over
 * a connection to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StandardExecutor implements IPipelineExecutor
{
    /**
     * Allows the pipeline executor to perform operations on the
     * connection before starting to execute the commands stored
     * in the pipeline.
     *
     * @param IConnection Connection instance.
     */
    protected function checkConnection(IConnection $connection)
    {
        if ($connection instanceof IConnectionReplication) {
            $connection->switchTo('master');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(IConnection $connection, &$commands)
    {
        $sizeofPipe = count($commands);
        $values = array();

        $this->checkConnection($connection);

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        try {
            for ($i = 0; $i < $sizeofPipe; $i++) {
                $response = $connection->readResponse($commands[$i]);
                $values[] = $response instanceof \Iterator
                    ? iterator_to_array($response)
                    : $response;
                unset($commands[$i]);
            }
        }
        catch (ServerException $exception) {
            // Force disconnection to prevent protocol desynchronization.
            $connection->disconnect();
            throw $exception;
        }

        return $values;
    }
}
