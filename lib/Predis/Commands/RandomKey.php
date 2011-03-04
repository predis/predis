<?php

namespace Predis\Commands;

class RandomKey extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}
