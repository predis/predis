<?php

namespace Predis\Options;

use Predis\Commands\Processors\KeyPrefixProcessor;

class ClientPrefix extends Option {
    public function validate($value) {
        return new KeyPrefixProcessor($value);
    }
}
