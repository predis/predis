<?php

namespace Predis\Commands;

use Predis\Command;

class ListInsert extends Command {
    public function getCommandId() { return 'LINSERT'; }
}
