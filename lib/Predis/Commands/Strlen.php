<?php

namespace Predis\Commands;

use Predis\Command;

class Strlen extends Command {
    public function getCommandId() { return 'STRLEN'; }
}
