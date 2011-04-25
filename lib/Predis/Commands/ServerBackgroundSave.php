<?php

namespace Predis\Commands;

class ServerBackgroundSave extends Command {
    public function getId() {
        return 'BGSAVE';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}
