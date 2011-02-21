<?php

namespace Predis\Pipeline;

use Predis\Network\IConnection;

class StandardExecutor implements IPipelineExecutor {
    public function execute(IConnection $connection, &$commands) {
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        try {
            for ($i = 0; $i < $sizeofPipe; $i++) {
                $response = $connection->readResponse($commands[$i]);
                $values[] = $response instanceof Iterator
                    ? iterator_to_array($response)
                    : $response;
                unset($commands[$i]);
            }
        }
        catch (\Predis\ServerException $exception) {
            // Force disconnection to prevent protocol desynchronization.
            $connection->disconnect();
            throw $exception;
        }

        return $values;
    }
}
