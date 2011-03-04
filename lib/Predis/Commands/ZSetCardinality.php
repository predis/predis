<?php

namespace Predis\Commands;

class ZSetCardinality extends Command {
    public function getCommandId() { return 'ZCARD'; }
}
