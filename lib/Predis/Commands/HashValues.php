<?php

namespace Predis\Commands;

use Predis\Command;

class HashValues extends Command {
    public function getCommandId() { return 'HVALS'; }
}
