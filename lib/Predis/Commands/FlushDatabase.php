<?php

namespace Predis\Commands;

use Predis\Command;

class FlushDatabase extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}
