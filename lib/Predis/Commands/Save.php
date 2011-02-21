<?php

namespace Predis\Commands;

use Predis\Command;

class Save extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}
