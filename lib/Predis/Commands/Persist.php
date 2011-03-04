<?php

namespace Predis\Commands;

class Persist extends Command {
    public function getId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}
