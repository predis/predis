<?php

namespace Predis\Commands;

class ZSetCount extends Command {
    public function getCommandId() { return 'ZCOUNT'; }
}
