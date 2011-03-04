<?php

namespace Predis\Commands;

class Expire extends Command {
    public function getId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}
