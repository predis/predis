<?php

namespace Predis\Commands;

class Config extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'CONFIG'; }
}
