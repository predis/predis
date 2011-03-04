<?php

namespace Predis\Commands;

class ZSetRemoveRangeByScore extends Command {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}
