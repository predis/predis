<?php

namespace Predis\Options;

class ClientIterableMultiBulk extends Option {
    public function validate($value) {
        return (bool) $value;
    }

    public function getDefault() {
        return false;
    }
}
