<?php

namespace Predis\Commands;

class ConnectionAuth extends Command {
    public function getId() {
        return 'AUTH';
    }

    protected function canBeHashed() {
        return false;
    }
}
