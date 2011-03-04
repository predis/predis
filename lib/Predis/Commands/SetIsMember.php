<?php

namespace Predis\Commands;

class SetIsMember extends Command {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}
