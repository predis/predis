<?php

namespace Predis\Commands;

class DoEcho extends Command {
    public function getId() {
        return 'ECHO';
    }

    protected function canBeHashed() {
        return false;
    }
}
