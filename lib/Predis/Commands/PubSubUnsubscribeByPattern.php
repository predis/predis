<?php

namespace Predis\Commands;

class PubSubUnsubscribeByPattern extends Command {
    public function getId() {
        return 'PUNSUBSCRIBE';
    }

    protected function canBeHashed() {
        return false;
    }
}
