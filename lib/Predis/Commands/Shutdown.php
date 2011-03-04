<?php

namespace Predis\Commands;

class Shutdown extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'SHUTDOWN'; }
}
