<?php

namespace Predis\Commands;

use Predis\Command;

class Decrement extends Command {
    public function getCommandId() { return 'DECR'; }
}
