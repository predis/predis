<?php

namespace Predis\Commands;

use Predis\Utils;

class SetIntersection extends Command {
    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
    public function getId() { return 'SINTER'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
