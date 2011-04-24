<?php

namespace Predis;

use Predis\Network\IConnection;
use Predis\Network\IConnectionCluster;

class Helpers {
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

    public static function filterVariadicValues(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }

    public static function getKeyHashablePart($key) {
        $start = strpos($key, '{');
        if ($start !== false) {
            $end = strpos($key, '}', $start);
            if ($end !== false) {
                $key = substr($key, ++$start, $end - $start);
            }
        }
        return $key;
    }
}
