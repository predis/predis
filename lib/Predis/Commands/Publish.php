<?php

namespace Predis\Commands;

use Predis\Command;

class Publish extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUBLISH'; }
}
