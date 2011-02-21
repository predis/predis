<?php

namespace Predis\Commands;

use Predis\Command;

class DecrementBy extends Command {
    public function getCommandId() { return 'DECRBY'; }
}
