<?php

namespace Predis\Commands;

class DecrementBy extends Command {
    public function getCommandId() { return 'DECRBY'; }
}
