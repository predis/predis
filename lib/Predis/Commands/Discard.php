<?php

namespace Predis\Commands;

class Discard extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'DISCARD'; }
}
