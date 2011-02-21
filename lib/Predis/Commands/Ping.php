<?php

namespace Predis\Commands;

use Predis\Command;

class Ping extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}
