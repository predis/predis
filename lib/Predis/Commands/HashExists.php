<?php

namespace Predis\Commands;

class HashExists extends Command {
    public function getId() { return 'HEXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}
