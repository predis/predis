<?php

namespace Predis;

class ResponseQueued {
    public $skipParse = true;

    public function __toString() {
        return 'QUEUED';
    }

    public function __get($property) {
        return $property === 'queued';
    }

    public function __isset($property) {
        return $property === 'queued';
    }
}
