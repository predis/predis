<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetReverseRank extends Command {
    public function getCommandId() { return 'ZREVRANK'; }
}
