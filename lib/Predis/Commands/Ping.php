<?php

namespace Predis\Commands;

class Ping extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}
