<?php

namespace Predis\Commands;

class ServerFlushDatabase extends Command {
    public function getId() {
        return 'FLUSHDB';
    }

    protected function canBeHashed() {
        return false;
    }
}
