<?php

namespace Predis\Options;

use Predis\IConnectionFactory;
use Predis\ConnectionFactory;

class ClientConnectionFactory extends Option {
    public function validate($value) {
        if ($value instanceof IConnectionFactory) {
            return $value;
        }
        if (is_array($value)) {
            return new ConnectionFactory($value);
        }
    }

    public function getDefault() {
        return new ConnectionFactory();
    }
}
