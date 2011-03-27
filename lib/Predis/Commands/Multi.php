<?php

namespace Predis\Commands;

class Multi extends Command {
    public function getId() {
        return 'MULTI';
    }

    protected function canBeHashed() {
        return false;
    }
}
