<?php

namespace Predis\Options;

use Predis\ClientException;

class ClientClusterType extends Option {
    const CLUSTER_INTERFACE = '\Predis\Network\IConnectionCluster';
    const CLUSTER_PREDIS = '\Predis\Network\ConnectionCluster';

    public function validate($value) {
        switch ($value) {
            case 'client':
                return self::CLUSTER_PREDIS;
            default:
                return $this->checkClass($value);
        }
    }

    private function checkClass($class) {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf(self::CLUSTER_INTERFACE)) {
            throw new ClientException(
                "The class $class is not a valid cluster connection"
            );
        }
        return $class;
    }

    public function getDefault() {
        return self::CLUSTER_PREDIS;
    }
}
