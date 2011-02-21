<?php

namespace Predis\Commands;

use Predis\Command;

class ListPopLastPushHeadBlocking extends Command {
    public function getCommandId() { return 'BRPOPLPUSH'; }
}
