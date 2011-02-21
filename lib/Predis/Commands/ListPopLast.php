<?php

namespace Predis\Commands;

use Predis\Command;

class ListPopLast extends Command {
    public function getCommandId() { return 'RPOP'; }
}
