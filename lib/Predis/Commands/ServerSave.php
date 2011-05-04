<?php

namespace Predis\Commands;

class ServerSave extends Command {
    public function getId() {
        return 'SAVE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
