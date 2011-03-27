<?php

namespace Predis\Commands;

use Predis\Utils;

class SetIntersectionStore extends Command {
    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
    public function getId() { return 'SINTERSTORE'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
