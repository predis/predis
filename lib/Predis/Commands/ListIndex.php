<?php

namespace Predis\Commands;

class ListIndex extends Command {
    public function getCommandId() { return 'LINDEX'; }
}
