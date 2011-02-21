<?php

namespace Predis\Commands;

use Predis\Command;

class Keys extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
}
