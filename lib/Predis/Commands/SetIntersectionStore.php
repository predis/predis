<?php

namespace Predis\Commands;

use Predis\Utils;

class SetIntersectionStore extends Command {
    public function getId() {
        return 'SINTERSTORE';
    }

    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
}
