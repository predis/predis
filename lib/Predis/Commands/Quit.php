<?php

namespace Predis\Commands;

use Predis\Command;

class Quit extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}
