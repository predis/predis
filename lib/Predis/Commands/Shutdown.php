<?php

namespace Predis\Commands;

use Predis\Command;

class Shutdown extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}
