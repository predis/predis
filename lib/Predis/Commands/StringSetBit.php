<?php

namespace Predis\Commands;

class StringSetBit extends Command {
    public function getId() {
        return 'SETBIT';
    }
}
