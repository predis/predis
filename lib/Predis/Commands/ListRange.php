<?php

namespace Predis\Commands;

use Predis\Command;

class ListRange extends Command {
    public function getCommandId() { return 'LRANGE'; }
}
