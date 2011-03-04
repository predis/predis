<?php

namespace Predis\Commands;

class ZSetRemoveRangeByRank extends Command {
    public function getCommandId() { return 'ZREMRANGEBYRANK'; }
}
