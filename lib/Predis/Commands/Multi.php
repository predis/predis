<?php

namespace Predis\Commands;

use Predis\Command;

class Multi extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}
