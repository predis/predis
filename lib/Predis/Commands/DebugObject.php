<?php

namespace Predis\Commands;

use Predis\Helpers;

class DebugObject extends Command {
    public function getId() {
        return 'OBJECT';
    }

    protected function canBeHashed() {
        return false;
    }
}
