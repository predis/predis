<?php

namespace Predis\Commands;

class ListPopFirstBlocking extends Command {
    protected function canBeHashed() {
        return $this->checkSameHashForKeys(
            array_slice(($args = $this->getArguments()), 0, count($args) - 1)
        );
    }
    public function getId() { return 'BLPOP'; }
}
