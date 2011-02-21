<?php

namespace Predis\Commands;

use Predis\Command;

class SetBit extends Command {
    public function getCommandId() { return 'SETBIT'; }
}
