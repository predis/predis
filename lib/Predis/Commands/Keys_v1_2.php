<?php

namespace Predis\Commands;

class Keys_v1_2 extends Keys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}
