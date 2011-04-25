<?php

namespace Predis\Commands;

class ServerFlushAll extends Command {
    public function getId() {
        return 'FLUSHALL';
    }

    protected function canBeHashed() {
        return false;
    }
}
