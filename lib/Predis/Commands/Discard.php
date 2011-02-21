<?php

namespace Predis\Commands;

use Predis\Command;

class Discard extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DISCARD'; }
}
