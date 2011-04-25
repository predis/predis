<?php

namespace Predis\Commands;

class KeyKeys extends Command {
    public function getId() {
        return 'KEYS';
    }

    protected function canBeHashed() {
        return false;
    }
}
