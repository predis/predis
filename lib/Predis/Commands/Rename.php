<?php

namespace Predis\Commands;

class Rename extends Command {
    public function getId() {
        return 'RENAME';
    }

    protected function canBeHashed() {
        return false;
    }
}
