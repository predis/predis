<?php

namespace Predis\Commands;

class ServerDatabaseSize extends Command {
    public function getId() {
        return 'DBSIZE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
