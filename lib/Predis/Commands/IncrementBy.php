<?php

namespace Predis\Commands;

use Predis\Command;

class IncrementBy extends Command {
    public function getCommandId() { return 'INCRBY'; }
}
