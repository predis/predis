<?php

namespace Predis\Commands;

use Predis\Command;

class DatabaseSize extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}
