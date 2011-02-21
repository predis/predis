<?php

namespace Predis\Commands;

use Predis\Command;

class Append extends Command {
    public function getCommandId() { return 'APPEND'; }
}
