<?php

namespace Predis\Commands;

class MoveKey extends Command {
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
