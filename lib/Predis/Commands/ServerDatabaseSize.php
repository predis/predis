<?php

namespace Predis\Commands;

class ServerDatabaseSize extends Command {
    public function getId() {
        return 'DBSIZE';
    }

    protected function canBeHashed() {
        return false;
    }
}
