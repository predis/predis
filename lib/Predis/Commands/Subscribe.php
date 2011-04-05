<?php

namespace Predis\Commands;

use Predis\Helpers;

class Subscribe extends Command {
    public function getId() {
        return 'SUBSCRIBE';
    }

    public function filterArguments(Array $arguments) {
        return Helpers::filterArrayArguments($arguments);
    }

    protected function canBeHashed() {
        return false;
    }
}
