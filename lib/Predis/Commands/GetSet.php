<?php

namespace Predis\Commands;

use Predis\Command;

class GetSet extends Command {
    public function getCommandId() { return 'GETSET'; }
}
