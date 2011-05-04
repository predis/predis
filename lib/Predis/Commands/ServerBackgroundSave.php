<?php

namespace Predis\Commands;

class ServerBackgroundSave extends Command {
    public function getId() {
        return 'BGSAVE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
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
