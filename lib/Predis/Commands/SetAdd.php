<?php

namespace Predis\Commands;

use Predis\Command;

class SetAdd extends Command {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}
