<?php

namespace Predis\Commands;

class SetBit extends Command {
    public function getCommandId() { return 'SETBIT'; }
}
