<?php

namespace Predis\Commands;

class ZSetRemoveRangeByScore extends Command {
    public function getId() { return 'ZREMRANGEBYSCORE'; }
}
