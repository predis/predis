<?php

namespace Predis\Commands;

class ServerEvalSHA extends Command {
    public function getId() {
        return 'EVALSHA';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
