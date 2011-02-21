<?php

namespace Predis\Commands;

use Predis\Command;

class SetExpire extends Command {
    public function getCommandId() { return 'SETEX'; }
}
