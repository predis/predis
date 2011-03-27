<?php

namespace Predis\Commands;

class Discard extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'DISCARD'; }
}
