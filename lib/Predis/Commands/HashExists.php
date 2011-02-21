<?php

namespace Predis\Commands;

use Predis\Command;

class HashExists extends Command {
    public function getCommandId() { return 'HEXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}
