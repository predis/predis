<?php

namespace Predis\Options;

use Predis\Distribution\IDistributionStrategy;
use Predis\Distribution\HashRing;

class ClientKeyDistribution extends Option {
    public function validate($value) {
        if ($value instanceof IDistributionStrategy) {
            return $value;
        }
        if (is_string($value)) {
            $valueReflection = new \ReflectionClass($value);
            if ($valueReflection->isSubclassOf('\Predis\Distribution\IDistributionStrategy')) {
                return new $value;
            }
        }
        throw new \InvalidArgumentException('Invalid value for key distribution');
    }

    public function getDefault() {
        return new HashRing();
    }
}
