<?php

namespace Predis\Commands;

class BackgroundSave extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}
