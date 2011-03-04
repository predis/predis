<?php

namespace Predis\Commands;

class LastSave extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'LASTSAVE'; }
}
