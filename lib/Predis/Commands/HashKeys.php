<?php

namespace Predis\Commands;

use Predis\Command;

class HashKeys extends Command {
    public function getCommandId() { return 'HKEYS'; }
}
