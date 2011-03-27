<?php

namespace Predis\Commands;

class Keys extends Command {
    public function getId() {
        return 'KEYS';
    }

    protected function canBeHashed() {
        return false;
    }
}
