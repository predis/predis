<?php

namespace Predis\Commands;

class Publish extends Command {
    public function getId() {
        return 'PUBLISH';
    }

    protected function canBeHashed() {
        return false;
    }
}
