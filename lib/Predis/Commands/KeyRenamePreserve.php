<?php

namespace Predis\Commands;

class KeyRenamePreserve extends Command {
    public function getId() {
        return 'RENAMENX';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
