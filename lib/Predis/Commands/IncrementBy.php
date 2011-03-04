<?php

namespace Predis\Commands;

class IncrementBy extends Command {
    public function getId() { return 'INCRBY'; }
}
