<?php

namespace Predis\Commands;

class DatabaseSize extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'DBSIZE'; }
}
