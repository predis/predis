<?php

namespace Predis\Commands;

class DoEcho extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'ECHO'; }
}
