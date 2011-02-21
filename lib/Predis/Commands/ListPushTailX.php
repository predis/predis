<?php

namespace Predis\Commands;

use Predis\Command;

class ListPushTailX extends Command {
    public function getCommandId() { return 'RPUSHX'; }
}
