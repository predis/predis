<?php

namespace Predis\Commands;

class TransactionMulti extends Command {
    public function getId() {
        return 'MULTI';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
