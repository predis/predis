<?php

namespace Predis\Commands;

use Predis\Command;

class RenamePreserve extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}
