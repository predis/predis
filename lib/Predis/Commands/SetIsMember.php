<?php

namespace Predis\Commands;

use Predis\Command;

class SetIsMember extends Command {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}
