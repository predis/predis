<?php

namespace Predis\Commands;

class DatabaseSize extends Command {
    public function getId() {
        return 'DBSIZE';
    }

    protected function canBeHashed() {
        return false;
    }
}
