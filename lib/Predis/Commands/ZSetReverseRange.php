<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetReverseRange extends ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}
