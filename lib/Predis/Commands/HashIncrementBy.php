<?php

namespace Predis\Commands;

class HashIncrementBy extends Command {
    public function getId() { return 'HINCRBY'; }
}
