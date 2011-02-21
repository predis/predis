<?php

namespace Predis\Commands;

use Predis\Command;

class Persist extends Command {
    public function getCommandId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}
