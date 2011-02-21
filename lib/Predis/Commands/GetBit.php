<?php

namespace Predis\Commands;

use Predis\Command;

class GetBit extends Command {
    public function getCommandId() { return 'GETBIT'; }
}
