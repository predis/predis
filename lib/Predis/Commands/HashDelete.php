<?php

namespace Predis\Commands;

use Predis\Command;

class HashDelete extends Command {
    public function getCommandId() { return 'HDEL'; }
    public function parseResponse($data) { return (bool) $data; }
}
