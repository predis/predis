<?php

namespace Predis\Options;

class ClientThrowOnError extends Option {
    public function validate($value) {
        return (bool) $value;
    }

    public function getDefault() {
        return true;
    }
}
