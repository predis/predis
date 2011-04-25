<?php

namespace Predis\Commands;

class PubSubPublish extends Command {
    public function getId() {
        return 'PUBLISH';
    }

    protected function canBeHashed() {
        return false;
    }
}
