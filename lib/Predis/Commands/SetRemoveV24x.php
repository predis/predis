<?php

namespace Predis\Commands;

use Predis\Helpers;

class SetRemoveV24x extends Command {
    public function getId() {
        return 'SREM';
    }

    public function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
