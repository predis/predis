<?php

namespace Predis\Commands;

use Predis\Utils;

class Subscribe extends Command {
    public function getId() {
        return 'SUBSCRIBE';
    }

    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }

    protected function canBeHashed() {
        return false;
    }
}
