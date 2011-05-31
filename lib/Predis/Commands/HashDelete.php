<?php

namespace Predis\Commands;

use Predis\Helpers;

class HashDelete extends Command {
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
