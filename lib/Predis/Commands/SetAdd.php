<?php

namespace Predis\Commands;

class SetAdd extends Command {
    public function getId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}
