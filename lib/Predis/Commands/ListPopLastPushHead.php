<?php

namespace Predis\Commands;

class ListPopLastPushHead extends Command {
    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
    public function getId() { return 'RPOPLPUSH'; }
}
