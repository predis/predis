<?php

namespace Predis\Commands;

class ListPopFirstBlocking extends Command {
    public function getId() {
        return 'BLPOP';
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys(
            array_slice(($args = $this->getArguments()), 0, count($args) - 1)
        );
    }
}
