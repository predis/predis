<?php

namespace Predis\Commands;

class UnsubscribeByPattern extends Command {
    public function getId() {
        return 'PUNSUBSCRIBE';
    }

    protected function canBeHashed() {
        return false;
    }
}
