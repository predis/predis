<?php

namespace Predis\Commands;

class Expire extends Command {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}
