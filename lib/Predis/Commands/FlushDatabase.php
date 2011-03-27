<?php

namespace Predis\Commands;

class FlushDatabase extends Command {
    public function getId() {
        return 'FLUSHDB';
    }

    protected function canBeHashed() {
        return false;
    }
}
