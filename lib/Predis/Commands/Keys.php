<?php

namespace Predis\Commands;

class Keys extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'KEYS'; }
}
