<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetRank extends Command {
    public function getCommandId() { return 'ZRANK'; }
}
