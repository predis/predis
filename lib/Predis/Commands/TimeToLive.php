<?php

namespace Predis\Commands;

use Predis\Command;

class TimeToLive extends Command {
    public function getCommandId() { return 'TTL'; }
}
