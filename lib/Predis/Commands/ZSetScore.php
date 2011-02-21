<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetScore extends Command {
    public function getCommandId() { return 'ZSCORE'; }
}
