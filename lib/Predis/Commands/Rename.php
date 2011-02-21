<?php

namespace Predis\Commands;

use Predis\Command;

class Rename extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}
