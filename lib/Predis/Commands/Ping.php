<?php

namespace Predis\Commands;

class Ping extends Command {
    public function getId() {
        return 'PING';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}
