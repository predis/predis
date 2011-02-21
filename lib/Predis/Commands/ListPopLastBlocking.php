<?php

namespace Predis\Commands;

use Predis\Command;

class ListPopLastBlocking extends Command {
    public function getCommandId() { return 'BRPOP'; }
}
