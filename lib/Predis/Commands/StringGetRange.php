<?php

namespace Predis\Commands;

class StringGetRange extends Command {
    public function getId() {
        return 'GETRANGE';
    }
}
