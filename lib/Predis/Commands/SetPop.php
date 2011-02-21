<?php

namespace Predis\Commands;

use Predis\Command;

class SetPop  extends Command {
    public function getCommandId() { return 'SPOP'; }
}
