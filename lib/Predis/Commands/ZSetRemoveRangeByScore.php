<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetRemoveRangeByScore extends Command {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}
