<?php

namespace Predis\Commands;

use Predis\Command;

class MoveKey extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}
