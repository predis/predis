<?php

namespace Predis\Commands;

use Predis\Command;

class ListIndex extends Command {
    public function getCommandId() { return 'LINDEX'; }
}
