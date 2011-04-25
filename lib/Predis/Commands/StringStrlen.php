<?php

namespace Predis\Commands;

class StringStrlen extends Command {
    public function getId() {
        return 'STRLEN';
    }
}
