<?php

namespace Predis\Commands;

class DoEcho extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'ECHO'; }
}
