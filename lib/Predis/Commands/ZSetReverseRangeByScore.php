<?php

namespace Predis\Commands;

class ZSetReverseRangeByScore extends ZSetRangeByScore {
    public function getCommandId() { return 'ZREVRANGEBYSCORE'; }
}
