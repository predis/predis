<?php

namespace Predis\Commands;

use Predis\Command;

class ListSet extends Command {
    public function getCommandId() { return 'LSET'; }
}
