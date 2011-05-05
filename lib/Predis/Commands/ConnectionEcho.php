<?php

namespace Predis\Commands;

class ConnectionEcho extends Command {
    public function getId() {
        return 'ECHO';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
