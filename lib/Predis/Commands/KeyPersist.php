<?php

namespace Predis\Commands;

class KeyPersist extends Command {
    public function getId() {
        return 'PERSIST';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
