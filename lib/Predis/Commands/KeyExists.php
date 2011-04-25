<?php

namespace Predis\Commands;

class KeyExists extends Command {
    public function getId() {
        return 'EXISTS';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
