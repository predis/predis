<?php

namespace Predis\Commands;

class ConnectionAuth extends Command {
    public function getId() {
        return 'AUTH';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
