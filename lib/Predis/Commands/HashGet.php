<?php

namespace Predis\Commands;

use Predis\Command;

class HashGet extends Command {
    public function getCommandId() { return 'HGET'; }
}
