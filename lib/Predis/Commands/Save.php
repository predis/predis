<?php

namespace Predis\Commands;

class Save extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'SAVE'; }
}
