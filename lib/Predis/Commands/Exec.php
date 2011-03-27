<?php

namespace Predis\Commands;

class Exec extends Command {
    public function getId() {
        return 'EXEC';
    }

    protected function canBeHashed() {
        return false;
    }
}
