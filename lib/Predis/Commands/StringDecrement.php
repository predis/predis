<?php

namespace Predis\Commands;

class StringDecrement extends Command {
    public function getId() {
        return 'DECR';
    }
}
