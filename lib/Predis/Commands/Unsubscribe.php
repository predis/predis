<?php

namespace Predis\Commands;

use Predis\Command;

class Unsubscribe extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
}
