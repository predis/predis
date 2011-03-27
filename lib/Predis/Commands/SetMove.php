<?php

namespace Predis\Commands;

class SetMove extends Command {
    public function getId() {
        return 'SMOVE';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
