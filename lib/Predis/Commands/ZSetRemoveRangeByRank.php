<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetRemoveRangeByRank extends Command {
    public function getCommandId() { return 'ZREMRANGEBYRANK'; }
}
