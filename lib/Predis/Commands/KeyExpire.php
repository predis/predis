<?php

namespace Predis\Commands;

class KeyExpire extends Command {
    public function getId() {
        return 'EXPIRE';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
