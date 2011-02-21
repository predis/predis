<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetIncrementBy extends Command {
    public function getCommandId() { return 'ZINCRBY'; }
}
