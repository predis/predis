<?php

namespace Predis\Commands;

class LastSave extends Command {
    public function getId() {
        return 'LASTSAVE';
    }

    protected function canBeHashed() {
        return false;
    }
}
