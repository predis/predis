<?php

namespace Predis\Pipeline;

use Predis\Network\IConnection;

class FireAndForgetExecutor implements IPipelineExecutor {
    public function execute(IConnection $connection, &$commands) {
        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        $connection->disconnect();

        return array();
    }
}
