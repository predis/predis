<?php

namespace Predis\Commands;

class BackgroundSave extends Command {
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
