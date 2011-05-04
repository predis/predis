<?php

namespace Predis\Commands;

class ServerFlushAll extends Command {
    public function getId() {
        return 'FLUSHALL';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
