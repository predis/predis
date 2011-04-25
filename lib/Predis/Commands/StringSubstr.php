<?php

namespace Predis\Commands;

class StringSubstr extends Command {
    public function getId() {
        return 'SUBSTR';
    }
}
