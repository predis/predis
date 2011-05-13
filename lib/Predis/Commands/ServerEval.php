<?php

namespace Predis\Commands;

class ServerEval extends Command {
    public function getId() {
        return 'EVAL';
    }

    protected function canBeHashed() {
        return false;
    }
}
