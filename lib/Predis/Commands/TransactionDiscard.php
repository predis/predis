<?php

namespace Predis\Commands;

class TransactionDiscard extends Command {
    public function getId() {
        return 'DISCARD';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
