<?php

namespace Predis\Commands;

class KeyKeysV12x extends KeyKeys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}
