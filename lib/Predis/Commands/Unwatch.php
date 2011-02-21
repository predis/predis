<?php

namespace Predis\Commands;

use Predis\Command;

class Unwatch extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNWATCH'; }
    public function parseResponse($data) { return (bool) $data; }
}
