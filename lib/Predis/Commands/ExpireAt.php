<?php

namespace Predis\Commands;

class ExpireAt extends Command {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}
