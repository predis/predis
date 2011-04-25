<?php

namespace Predis\Commands;

class TransactionUnwatch extends Command {
    public function getId() {
        return 'UNWATCH';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
