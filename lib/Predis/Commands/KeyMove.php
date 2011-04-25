<?php

namespace Predis\Commands;

class KeyMove extends Command {
    public function getId() {
        return 'MOVE';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
