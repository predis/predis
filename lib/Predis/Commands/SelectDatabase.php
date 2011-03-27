<?php

namespace Predis\Commands;

class SelectDatabase extends Command {
    public function getId() {
        return 'SELECT';
    }

    protected function canBeHashed() {
        return false;
    }
}
