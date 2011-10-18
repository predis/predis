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
use Predis\CommunicationException;
use Predis\Network\IConnection;

/**
 * Implements a pipeline executor strategy for connection clusters that does
 * not fail when an error is encountered, but adds the returned error in the
 * replies array.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SafeClusterExecutor implements IPipelineExecutor
{
    /**
     * {@inheritdoc}
     */
    public function execute(IConnection $connection, &$commands)
    {
        $connectionExceptions = array();
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            $cmdConnection = $connection->getConnection($command);

            if (isset($connectionExceptions[spl_object_hash($cmdConnection)])) {
                continue;
            }

            try {
                $cmdConnection->writeCommand($command);
            }
            catch (CommunicationException $exception) {
                $connectionExceptions[spl_object_hash($cmdConnection)] = $exception;
            }
        }

        for ($i = 0; $i < $sizeofPipe; $i++) {
            $command = $commands[$i];
            unset($commands[$i]);

            $cmdConnection = $connection->getConnection($command);
            $connectionObjectHash = spl_object_hash($cmdConnection);

            if (isset($connectionExceptions[$connectionObjectHash])) {
                $values[] = $connectionExceptions[$connectionObjectHash];
                continue;
            }

            try {
                $response = $cmdConnection->readResponse($command);
                $values[] = $response instanceof \Iterator ? iterator_to_array($response) : $response;
            }
            catch (ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (CommunicationException $exception) {
                $values[] = $exception;
                $connectionExceptions[$connectionObjectHash] = $exception;
            }
        }

        return $values;
    }
}
