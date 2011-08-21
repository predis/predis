<?php

namespace Predis\Commands;

use Predis\Helpers;

class HashGetMultiple extends Command {
    public function getId() {
        return 'HMGET';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }
}
