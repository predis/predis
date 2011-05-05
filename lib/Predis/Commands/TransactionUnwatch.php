<?php

namespace Predis\Commands;

class TransactionUnwatch extends Command {
    public function getId() {
        return 'UNWATCH';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
