<?php

namespace Predis\Commands;

use Predis\Command;

class ZSetRemove extends Command {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
