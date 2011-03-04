<?php

namespace Predis\Commands;

class ZSetRemoveRangeByRank extends Command {
    public function getId() { return 'ZREMRANGEBYRANK'; }
}
