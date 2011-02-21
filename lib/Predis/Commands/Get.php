<?php

namespace Predis\Commands;

use Predis\Command;

class Get extends Command {
    public function getCommandId() { return 'GET'; }
}
