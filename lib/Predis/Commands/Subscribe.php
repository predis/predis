<?php

namespace Predis\Commands;

class Subscribe extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'SUBSCRIBE'; }
}
