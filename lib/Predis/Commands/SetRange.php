<?php

namespace Predis\Commands;

use Predis\Command;

class SetRange extends Command {
    public function getCommandId() { return 'SETRANGE'; }
}
