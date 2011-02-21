<?php

namespace Predis\Commands;

use Predis\Command;

class ListTrim extends Command {
    public function getCommandId() { return 'LTRIM'; }
}
