<?php

namespace Predis\Commands;

class ConnectionSelect extends Command {
    public function getId() {
        return 'SELECT';
    }

    protected function canBeHashed() {
        return false;
    }
}
