<?php

namespace Predis\Commands;

class LastSave extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'LASTSAVE'; }
}
