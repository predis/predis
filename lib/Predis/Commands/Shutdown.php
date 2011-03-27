<?php

namespace Predis\Commands;

class Shutdown extends Command {
    public function getId() {
        return 'SHUTDOWN';
    }

    protected function canBeHashed() {
        return false;
    }
}
