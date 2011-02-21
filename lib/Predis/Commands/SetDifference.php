<?php

namespace Predis\Commands;

use Predis\Command;

class SetDifference extends SetIntersection {
    public function getCommandId() { return 'SDIFF'; }
}
