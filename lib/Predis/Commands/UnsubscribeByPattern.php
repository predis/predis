<?php

namespace Predis\Commands;

use Predis\Command;

class UnsubscribeByPattern extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUNSUBSCRIBE'; }
}
