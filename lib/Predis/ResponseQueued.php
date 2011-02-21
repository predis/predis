<?php

namespace Predis;

class ResponseQueued {
    public $skipParse = true;

    public function __toString() {
        return 'QUEUED';
    }

    public function __get($property) {
        if ($property === 'queued') {
            return true;
        }
    }

    public function __isset($property) {
        return $property === 'queued';
    }
}
