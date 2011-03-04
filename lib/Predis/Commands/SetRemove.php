<?php

namespace Predis\Commands;

class SetRemove extends Command {
    public function getId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}
