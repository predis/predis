<?php

namespace Predis\Commands;

class SetMove extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}
