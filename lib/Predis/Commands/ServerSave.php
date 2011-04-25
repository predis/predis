<?php

namespace Predis\Commands;

class ServerSave extends Command {
    public function getId() {
        return 'SAVE';
    }

    protected function canBeHashed() {
        return false;
    }
}
