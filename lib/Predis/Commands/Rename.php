<?php

namespace Predis\Commands;

class Rename extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}
