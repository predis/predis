<?php

namespace Predis\Commands;

class FlushAll extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'FLUSHALL'; }
}
