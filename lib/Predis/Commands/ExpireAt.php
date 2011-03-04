<?php

namespace Predis\Commands;

class ExpireAt extends Command {
    public function getId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}
