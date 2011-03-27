<?php

namespace Predis\Commands;

class Multi extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'MULTI'; }
}
