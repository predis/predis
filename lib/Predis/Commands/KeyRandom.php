<?php

namespace Predis\Commands;

class KeyRandom extends Command {
    public function getId() {
        return 'RANDOMKEY';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return $data !== '' ? $data : null;
    }
}
