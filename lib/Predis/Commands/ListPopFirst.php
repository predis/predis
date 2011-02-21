<?php

namespace Predis\Commands;

use Predis\Command;

class ListPopFirst extends Command {
    public function getCommandId() { return 'LPOP'; }
}
