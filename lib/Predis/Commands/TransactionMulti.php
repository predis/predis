<?php

namespace Predis\Commands;

class TransactionMulti extends Command {
    public function getId() {
        return 'MULTI';
    }

    protected function canBeHashed() {
        return false;
    }
}
