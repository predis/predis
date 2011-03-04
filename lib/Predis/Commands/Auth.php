<?php

namespace Predis\Commands;

class Auth extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'AUTH'; }
}
