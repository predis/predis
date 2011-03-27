<?php

namespace Predis\Commands;

class Config extends Command {
    public function getId() {
        return 'CONFIG';
    }

    protected function canBeHashed() {
        return false;
    }
}
