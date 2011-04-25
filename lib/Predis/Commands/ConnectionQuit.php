<?php

namespace Predis\Commands;

class ConnectionQuit extends Command {
    public function getId() {
        return 'QUIT';
    }

    protected function canBeHashed() {
        return false;
    }
}
