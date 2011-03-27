<?php

namespace Predis\Commands;

class FlushDatabase extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'FLUSHDB'; }
}
