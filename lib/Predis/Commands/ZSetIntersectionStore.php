<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetIntersectionStore extends ZSetUnionStore {
    public function getCommandId() { return 'ZINTERSTORE'; }
}
