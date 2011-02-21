<?php

namespace Predis\Commands;

use Predis\Command;

class Substr extends Command {
    public function getCommandId() { return 'SUBSTR'; }
}
