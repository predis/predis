<?php

namespace Predis\Commands;

use Predis\Command;

class LastSave extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}
