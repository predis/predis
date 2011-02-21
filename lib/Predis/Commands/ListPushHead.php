<?php

namespace Predis\Commands;

use Predis\Command;

class ListPushHead extends Command {
    public function getCommandId() { return 'LPUSH'; }
}
