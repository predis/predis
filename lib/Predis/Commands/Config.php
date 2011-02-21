<?php

namespace Predis\Commands;

use Predis\Command;

class Config extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'CONFIG'; }
}
