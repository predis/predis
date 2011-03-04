<?php

namespace Predis;

use Predis\Network\IConnection;
use Predis\Network\IConnectionCluster;

class Utils {
    public static function isCluster(IConnection $connection) {
        return $connection instanceof IConnectionCluster;
    }

    public static function onCommunicationException(CommunicationException $exception) {
        if ($exception->shouldResetConnection()) {
            $connection = $exception->getConnection();
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
        }
        throw $exception;
    }

    public static function filterArrayArguments(Array $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }
}
