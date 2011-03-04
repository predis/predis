<?php

namespace Predis\Commands;

class ZSetReverseRank extends Command {
    public function getId() { return 'ZREVRANK'; }
}
