<?php

namespace Predis\Commands;

class Exec extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'EXEC'; }
}
