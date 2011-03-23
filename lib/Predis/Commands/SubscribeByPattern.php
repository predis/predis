<?php

namespace Predis\Commands;

class SubscribeByPattern extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'PSUBSCRIBE'; }
}
