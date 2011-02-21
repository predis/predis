<?php

namespace Predis\Commands;

use Predis\Command;

class Type extends Command {
    public function getCommandId() { return 'TYPE'; }
}
