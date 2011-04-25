<?php

namespace Predis\Commands;

class StringGetSet extends Command {
    public function getId() {
        return 'GETSET';
    }
}
