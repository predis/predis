<?php

namespace Predis\Commands;

use Predis\Command;

class HashIncrementBy extends Command {
    public function getCommandId() { return 'HINCRBY'; }
}
