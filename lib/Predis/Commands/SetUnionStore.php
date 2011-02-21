<?php

namespace Predis\Commands;

use Predis\Command;

class SetUnionStore extends SetIntersectionStore {
    public function getCommandId() { return 'SUNIONSTORE'; }
}
