<?php

namespace Predis\Commands;

use Predis\Command;

class Increment extends Command {
    public function getCommandId() { return 'INCR'; }
}
