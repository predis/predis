<?php

namespace Predis\Commands;

use Predis\Command;

class Subscribe extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SUBSCRIBE'; }
}
