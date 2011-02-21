<?php

namespace Predis\Commands;

use Predis\Command;

class SetUnion extends SetIntersection {
    public function getCommandId() { return 'SUNION'; }
}
