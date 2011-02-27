<?php

namespace Predis\Commands;

class KeysV12x extends Keys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}
