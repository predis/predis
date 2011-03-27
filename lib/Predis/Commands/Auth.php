<?php

namespace Predis\Commands;

class Auth extends Command {
    public function getId() {
        return 'AUTH';
    }

    protected function canBeHashed() {
        return false;
    }
}
