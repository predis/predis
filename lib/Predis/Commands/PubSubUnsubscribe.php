<?php

namespace Predis\Commands;

class PubSubUnsubscribe extends Command {
    public function getId() {
        return 'UNSUBSCRIBE';
    }

    protected function canBeHashed() {
        return false;
    }
}
