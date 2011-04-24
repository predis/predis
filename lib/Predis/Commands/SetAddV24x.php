<?php

namespace Predis\Commands;

use Predis\Helpers;

class SetAddV24x extends Command {
    public function getId() {
        return 'SADD';
    }

    public function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
