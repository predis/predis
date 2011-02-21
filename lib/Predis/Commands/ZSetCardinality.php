<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetCardinality extends Command {
    public function getCommandId() { return 'ZCARD'; }
}
