<?php

namespace Predis\Commands;

use Predis\Command;

class SetMove extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}
