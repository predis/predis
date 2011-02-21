<?php

namespace Predis\Commands;

use Predis\Command;

class SubscribeByPattern extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PSUBSCRIBE'; }
}
