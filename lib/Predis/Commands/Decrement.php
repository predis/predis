<?php

namespace Predis\Commands;

class Decrement extends Command {
    public function getCommandId() { return 'DECR'; }
}
