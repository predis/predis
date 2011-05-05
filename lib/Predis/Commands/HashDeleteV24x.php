<?php

namespace Predis\Commands;

use Predis\Helpers;

class HashDeleteV24x extends Command {
    public function getId() {
        return 'HDEL';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
