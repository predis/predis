<?php

namespace Predis\Commands;

use Predis\Command;

class HashSet extends Command {
    public function getCommandId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}
