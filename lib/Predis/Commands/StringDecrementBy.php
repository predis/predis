<?php

namespace Predis\Commands;

class StringDecrementBy extends Command {
    public function getId() {
        return 'DECRBY';
    }
}
