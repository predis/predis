<?php

namespace Predis\Commands;

class Quit extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'QUIT'; }
}
