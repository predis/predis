<?php

namespace Predis\Commands;

use Predis\Command;

class ListRemove extends Command {
    public function getCommandId() { return 'LREM'; }
}
