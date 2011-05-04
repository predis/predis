<?php

namespace Predis\Commands;

class TransactionExec extends Command {
    public function getId() {
        return 'EXEC';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
