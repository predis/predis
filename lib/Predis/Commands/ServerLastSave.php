<?php

namespace Predis\Commands;

class ServerLastSave extends Command {
    public function getId() {
        return 'LASTSAVE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
