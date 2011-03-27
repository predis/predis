<?php

namespace Predis\Commands;

class ListPopLastPushHead extends Command {
    public function getId() {
        return 'RPOPLPUSH';
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
}
