<?php

namespace Predis\Commands;

class KeyExpireAt extends Command {
    public function getId() {
        return 'EXPIREAT';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
