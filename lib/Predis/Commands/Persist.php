<?php

namespace Predis\Commands;

class Persist extends Command {
    public function getCommandId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}
