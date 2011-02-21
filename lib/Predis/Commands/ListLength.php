<?php

namespace Predis\Commands;

use Predis\Command;

class ListLength extends Command {
    public function getCommandId() { return 'LLEN'; }
}
