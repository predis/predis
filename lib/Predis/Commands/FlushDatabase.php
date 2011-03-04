<?php

namespace Predis\Commands;

class FlushDatabase extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'FLUSHDB'; }
}
