<?php

namespace Predis\Commands;

class SetCardinality extends Command {
    public function getCommandId() { return 'SCARD'; }
}
