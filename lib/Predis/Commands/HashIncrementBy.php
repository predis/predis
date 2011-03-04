<?php

namespace Predis\Commands;

class HashIncrementBy extends Command {
    public function getCommandId() { return 'HINCRBY'; }
}
