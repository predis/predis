<?php

namespace Predis\Commands;

class StringGetBit extends Command {
    public function getId() {
        return 'GETBIT';
    }
}
