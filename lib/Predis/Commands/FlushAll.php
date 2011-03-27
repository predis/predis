<?php

namespace Predis\Commands;

class FlushAll extends Command {
    public function getId() {
        return 'FLUSHALL';
    }

    protected function canBeHashed() {
        return false;
    }
}
