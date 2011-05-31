<?php

namespace Predis\Commands;

use Predis\Helpers;

class SetRemove extends Command {
    public function getId() {
        return 'SREM';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
