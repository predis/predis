<?php

namespace Predis\Commands;

class Save extends Command {
    public function getId() {
        return 'SAVE';
    }

    protected function canBeHashed() {
        return false;
    }
}
