<?php

namespace Predis\Commands;

class SetRemove extends Command {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
