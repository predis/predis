<?php

namespace Predis\Commands;

use Predis\Command;

class ListPopFirstBlocking extends Command {
    public function getCommandId() { return 'BLPOP'; }
}
