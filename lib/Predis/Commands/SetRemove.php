<?php

namespace Predis\Commands;

use Predis\Command;

class SetRemove extends Command {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
