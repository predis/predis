<?php

namespace Predis\Commands;

class RandomKey extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}
