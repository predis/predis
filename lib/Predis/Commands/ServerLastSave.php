<?php

namespace Predis\Commands;

class ServerLastSave extends Command {
    public function getId() {
        return 'LASTSAVE';
    }

    protected function canBeHashed() {
        return false;
    }
}
