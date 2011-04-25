<?php

namespace Predis\Commands;

class StringIncrement extends Command {
    public function getId() {
        return 'INCR';
    }
}
