<?php

namespace Predis\Commands;

class HashSetPreserve extends Command {
    public function getCommandId() { return 'HSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}
