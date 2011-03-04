<?php

namespace Predis\Commands;

class FlushAll extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'FLUSHALL'; }
}
