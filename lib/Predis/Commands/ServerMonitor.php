<?php

namespace Predis\Commands;

class ServerMonitor extends Command {
    public function getId() {
        return 'MONITOR';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
