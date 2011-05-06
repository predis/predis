<?php

namespace Predis\Options;

use Predis\Network\IConnectionCluster;
use Predis\Network\PredisCluster;

class ClientCluster extends Option {
    protected function checkInstance($cluster) {
        if (!$cluster instanceof IConnectionCluster) {
            throw new \InvalidArgumentException(
                'Instance of Predis\Network\IConnectionCluster expected'
            );
        }
        return $cluster;
    }

    public function validate($value) {
        if (is_callable($value)) {
            return $this->checkInstance(call_user_func($value));
        }
        $initializer = $this->getInitializer($value);
        return $this->checkInstance($initializer());
    }

    protected function getInitializer($fqnOrType) {
        switch ($fqnOrType) {
            case 'predis':
                return function() { return new PredisCluster(); };
            default:
                return function() use($fqnOrType) {
                    return new $fqnOrType();
                };
        }
    }

    public function getDefault() {
        return new PredisCluster();
    }
}
