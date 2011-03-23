<?php

namespace Predis\Commands;

class Rename extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'RENAME'; }
}
