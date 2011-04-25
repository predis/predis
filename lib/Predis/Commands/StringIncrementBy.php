<?php

namespace Predis\Commands;

class StringIncrementBy extends Command {
    public function getId() {
        return 'INCRBY';
    }
}
