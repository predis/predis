<?php

namespace Predis\Commands;

class HashSet extends Command {
    public function getId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}
