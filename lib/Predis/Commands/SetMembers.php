<?php

namespace Predis\Commands;

use Predis\Command;

class SetMembers extends Command {
    public function getCommandId() { return 'SMEMBERS'; }
}
