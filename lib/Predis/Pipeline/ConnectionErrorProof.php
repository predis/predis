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
use Predis\NotSupportedException;
use Predis\CommunicationException;
use Predis\Connection\ClusterConnectionInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\SingleConnectionInterface;

/**
 * Command pipeline that does not throw exceptions on connection errors, but
 * returns the exception instances as the rest of the response elements.
 *
 * @todo Awful naming!
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionErrorProof extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        if ($connection instanceof SingleConnectionInterface) {
            return $this->executePipelineNode($connection, $commands);
        } else if ($connection instanceof ClusterConnectionInterface) {
            return $this->executePipelineCluster($connection, $commands);
        } else {
            throw new NotSupportedException("Unsupported connection type");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executePipelineNode(SingleConnectionInterface $connection, SplQueue $commands)
    {
        $responses  = array();
        $sizeOfPipe = count($commands);

        foreach ($commands as $command) {
            try {
                $connection->writeCommand($command);
            } catch (CommunicationException $exception) {
                return array_fill(0, $sizeOfPipe, $exception);
            }
        }

        for ($i = 0; $i < $sizeOfPipe; $i++) {
            $command = $commands->dequeue();

            try {
                $responses[$i] = $connection->readResponse($command);
            } catch (CommunicationException $exception) {
                $add = count($commands) - count($responses);
                $responses = array_merge($responses, array_fill(0, $add, $exception));

                break;
            }
        }

        return $responses;
    }

    /**
     * {@inheritdoc}
     */
    public function executePipelineCluster(ClusterConnectionInterface $connection, SplQueue $commands)
    {
        $responses  = array();
        $sizeOfPipe = count($commands);
        $exceptions = array();

        foreach ($commands as $command) {
            $cmdConnection = $connection->getConnection($command);

            if (isset($exceptions[spl_object_hash($cmdConnection)])) {
                continue;
            }

            try {
                $cmdConnection->writeCommand($command);
            } catch (CommunicationException $exception) {
                $exceptions[spl_object_hash($cmdConnection)] = $exception;
            }
        }

        for ($i = 0; $i < $sizeOfPipe; $i++) {
            $command = $commands->dequeue();

            $cmdConnection = $connection->getConnection($command);
            $connectionHash = spl_object_hash($cmdConnection);

            if (isset($exceptions[$connectionHash])) {
                $responses[$i] = $exceptions[$connectionHash];
                continue;
            }

            try {
                $responses[$i] = $cmdConnection->readResponse($command);
            } catch (CommunicationException $exception) {
                $responses[$i] = $exception;
                $exceptions[$connectionHash] = $exception;
            }
        }

        return $responses;
    }
}
