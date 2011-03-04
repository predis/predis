<?php

namespace Predis\Commands;

class ZSetIncrementBy extends Command {
    public function getCommandId() { return 'ZINCRBY'; }
}
