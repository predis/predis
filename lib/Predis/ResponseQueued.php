<?php

namespace Predis;

class ResponseQueued implements IReplyObject {
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
