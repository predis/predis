<?php

namespace Predis\Commands;

use Predis\Command;

class Exists extends Command {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}
