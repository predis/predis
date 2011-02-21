<?php

namespace Predis\Commands;

use Predis\Command;

class FlushAll extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}
