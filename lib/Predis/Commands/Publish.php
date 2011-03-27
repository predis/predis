<?php

namespace Predis\Commands;

class Publish extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'PUBLISH'; }
}
