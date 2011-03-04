<?php

namespace Predis\Commands;

class HashSet extends Command {
    public function getCommandId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}
