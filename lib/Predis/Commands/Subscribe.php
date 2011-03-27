<?php

namespace Predis\Commands;

use Predis\Utils;

class Subscribe extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'SUBSCRIBE'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
