<?php

namespace Predis\Commands;

use Predis\Helpers;

class ZSetRemove extends Command {
    public function getId() {
        return 'ZREM';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterVariadicValues($arguments);
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
