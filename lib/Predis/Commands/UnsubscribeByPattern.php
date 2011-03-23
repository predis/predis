<?php

namespace Predis\Commands;

class UnsubscribeByPattern extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'PUNSUBSCRIBE'; }
}
