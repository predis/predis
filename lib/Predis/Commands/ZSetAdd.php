<?php

namespace Predis\Commands;

class ZSetAdd extends Command {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}
