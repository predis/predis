<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetCount extends Command {
    public function getCommandId() { return 'ZCOUNT'; }
}
