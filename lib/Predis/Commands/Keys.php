<?php

namespace Predis\Commands;

class Keys extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'KEYS'; }
}
