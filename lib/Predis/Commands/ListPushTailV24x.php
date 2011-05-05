<?php

namespace Predis\Commands;

use Predis\Helpers;

class ListPushTailV24x extends Command {
    public function getId() {
        return 'RPUSH';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }
}
