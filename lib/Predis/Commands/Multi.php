<?php

namespace Predis\Commands;

class Multi extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}
