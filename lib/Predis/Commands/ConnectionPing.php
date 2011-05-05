<?php

namespace Predis\Commands;

class ConnectionPing extends Command {
    public function getId() {
        return 'PING';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}
