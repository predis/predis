<?php

namespace Predis\Commands;

class ZSetRemove extends Command {
    public function getId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
