<?php

namespace Predis\Commands;

class Auth extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'AUTH'; }
}
