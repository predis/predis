<?php

namespace Predis\Commands;

class Exec extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'EXEC'; }
}
