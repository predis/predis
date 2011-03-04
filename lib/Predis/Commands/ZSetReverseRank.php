<?php

namespace Predis\Commands;

class ZSetReverseRank extends Command {
    public function getCommandId() { return 'ZREVRANK'; }
}
