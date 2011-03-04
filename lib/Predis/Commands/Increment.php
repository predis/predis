<?php

namespace Predis\Commands;

class Increment extends Command {
    public function getCommandId() { return 'INCR'; }
}
