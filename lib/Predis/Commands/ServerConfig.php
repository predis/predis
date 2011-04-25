<?php

namespace Predis\Commands;

class ServerConfig extends Command {
    public function getId() {
        return 'CONFIG';
    }

    protected function canBeHashed() {
        return false;
    }
}
