<?php

namespace Predis\Commands;

class DecrementBy extends Command {
    public function getId() { return 'DECRBY'; }
}
