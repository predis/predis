<?php

namespace Predis\Commands;

use Predis\Command;

class ListPushTail extends Command {
    public function getCommandId() { return 'RPUSH'; }
}
