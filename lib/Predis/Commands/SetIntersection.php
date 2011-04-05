<?php

namespace Predis\Commands;

use Predis\Helpers;

class SetIntersection extends Command {
    public function getId() {
        return 'SINTER';
    }

    public function filterArguments(Array $arguments) {
        return Helpers::filterArrayArguments($arguments);
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
}
