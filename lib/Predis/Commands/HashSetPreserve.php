<?php

namespace Predis\Commands;

use Predis\Command;

class HashSetPreserve extends Command {
    public function getCommandId() { return 'HSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}
