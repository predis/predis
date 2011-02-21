<?php

namespace Predis\Commands;

use Predis\Command;

class HashLength extends Command {
    public function getCommandId() { return 'HLEN'; }
}
