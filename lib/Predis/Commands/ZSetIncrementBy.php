<?php

namespace Predis\Commands;

class ZSetIncrementBy extends Command {
    public function getId() { return 'ZINCRBY'; }
}
