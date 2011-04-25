<?php

namespace Predis\Commands;

class StringAppend extends Command {
    public function getId() {
        return 'APPEND';
    }
}
