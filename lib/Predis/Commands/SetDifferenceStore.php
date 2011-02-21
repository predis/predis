<?php

namespace Predis\Commands;

use Predis\Command;

class SetDifferenceStore extends SetIntersectionStore {
    public function getCommandId() { return 'SDIFFSTORE'; }
}
