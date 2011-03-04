<?php

namespace Predis\Commands;

class Increment extends Command {
    public function getId() { return 'INCR'; }
}
