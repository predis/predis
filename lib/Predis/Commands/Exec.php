<?php

namespace Predis\Commands;

use Predis\Command;

class Exec extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}
