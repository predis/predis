<?php

namespace Predis\Commands;

class Unsubscribe extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
}
