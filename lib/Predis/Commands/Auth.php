<?php

namespace Predis\Commands;

use Predis\Command;

class Auth extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}
