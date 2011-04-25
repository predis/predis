<?php

namespace Predis\Commands;

class TransactionDiscard extends Command {
    public function getId() {
        return 'DISCARD';
    }

    protected function canBeHashed() {
        return false;
    }
}
