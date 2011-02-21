<?php

namespace Predis\Commands;

use Predis\Command;

class SetRandomMember extends Command {
    public function getCommandId() { return 'SRANDMEMBER'; }
}
