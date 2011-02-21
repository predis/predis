<?php

namespace Predis\Commands;

use Predis\Command;

class GetRange extends Command {
    public function getCommandId() { return 'GETRANGE'; }
}
