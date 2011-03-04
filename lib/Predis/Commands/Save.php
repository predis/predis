<?php

namespace Predis\Commands;

class Save extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'SAVE'; }
}
