<?php

namespace Predis\Pipeline;

use Predis\Network\IConnection;

class SafeClusterExecutor implements IPipelineExecutor {
    public function execute(IConnection $connection, &$commands) {
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
            catch (\Predis\CommunicationException $exception) {
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
                $values[] = ($response instanceof \Iterator
                    ? iterator_to_array($response)
                    : $response
                );
            }
            catch (\Predis\ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (\Predis\CommunicationException $exception) {
                $values[] = $exception;
                $connectionExceptions[$connectionObjectHash] = $exception;
            }
        }

        return $values;
    }
}
