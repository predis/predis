<?php

namespace Predis\Commands;

use Predis\Command;

class ListPushHeadX extends Command {
    public function getCommandId() { return 'LPUSHX'; }
}
