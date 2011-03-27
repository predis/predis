<?php

namespace Predis\Commands;

class Unsubscribe extends Command {
    public function getId() {
        return 'UNSUBSCRIBE';
    }

    protected function canBeHashed() {
        return false;
    }
}
