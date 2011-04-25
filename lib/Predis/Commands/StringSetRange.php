<?php

namespace Predis\Commands;

class StringSetRange extends Command {
    public function getId() {
        return 'SETRANGE';
    }
}
