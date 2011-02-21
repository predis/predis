<?php

namespace Predis\Commands;

use Predis\Command;

class RandomKey extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}
