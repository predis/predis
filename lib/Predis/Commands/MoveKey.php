<?php

namespace Predis\Commands;

class MoveKey extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}
