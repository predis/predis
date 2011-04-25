<?php

namespace Predis\Commands;

class ConnectionEcho extends Command {
    public function getId() {
        return 'ECHO';
    }

    protected function canBeHashed() {
        return false;
    }
}
