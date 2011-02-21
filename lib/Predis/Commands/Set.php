<?php

namespace Predis\Commands;

use Predis\Command;

class Set extends Command {
    public function getCommandId() { return 'SET'; }
}
