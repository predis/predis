<?php

namespace Predis\Commands;

class Quit extends Command {
    public function getId() {
        return 'QUIT';
    }

    protected function canBeHashed() {
        return false;
    }
}
