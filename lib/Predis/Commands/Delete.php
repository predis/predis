<?php

namespace Predis\Commands;

use Predis\Helpers;

class Delete extends Command {
    public function getId() {
        return 'DEL';
    }

    public function filterArguments(Array $arguments) {
        return Helpers::filterArrayArguments($arguments);
    }

    protected function canBeHashed() {
        $args = $this->getArguments();
        if (count($args) === 1) {
            return true;
        }
        return $this->checkSameHashForKeys($args);
    }
}
