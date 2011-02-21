<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetReverseRangeByScore extends ZSetRangeByScore {
    public function getCommandId() { return 'ZREVRANGEBYSCORE'; }
}
