<?php

namespace Predis\Commands;

class HashDelete extends Command {
    public function getId() { return 'HDEL'; }
    public function parseResponse($data) { return (bool) $data; }
}
