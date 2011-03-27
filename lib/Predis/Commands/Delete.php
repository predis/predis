<?php

namespace Predis\Commands;

use Predis\Utils;

class Delete extends Command {
    protected function canBeHashed() {
        $args = $this->getArguments();
        if (count($args) === 1) {
            return true;
        }
        return $this->checkSameHashForKeys($args);
    }
    public function getId() { return 'DEL'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
