<?php

namespace Predis\Commands;

class Discard extends Command {
    public function getId() {
        return 'DISCARD';
    }

    protected function canBeHashed() {
        return false;
    }
}
