<?php

namespace Predis\Commands;

use Predis\Command;

class SetCardinality extends Command {
    public function getCommandId() { return 'SCARD'; }
}
