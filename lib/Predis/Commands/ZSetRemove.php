<?php

namespace Predis\Commands;

class ZSetRemove extends Command {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
