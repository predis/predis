<?php

namespace Predis\Commands;

class IncrementBy extends Command {
    public function getCommandId() { return 'INCRBY'; }
}
