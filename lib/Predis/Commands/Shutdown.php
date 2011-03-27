<?php

namespace Predis\Commands;

class Shutdown extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'SHUTDOWN'; }
}
