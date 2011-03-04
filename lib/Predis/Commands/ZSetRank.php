<?php

namespace Predis\Commands;

class ZSetRank extends Command {
    public function getCommandId() { return 'ZRANK'; }
}
