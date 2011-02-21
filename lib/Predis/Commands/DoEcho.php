<?php

namespace Predis\Commands;

use Predis\Command;

class DoEcho extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}
