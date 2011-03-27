<?php

namespace Predis\Commands;

class MoveKey extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}
