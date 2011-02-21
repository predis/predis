<?php

namespace Predis\Commands;

use Predis\Command;

class ExpireAt extends Command {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}
