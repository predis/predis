<?php

namespace Predis\Commands;

use Predis\Command;

class SetMultiplePreserve extends SetMultiple {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}
