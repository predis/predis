<?php

namespace Predis\Commands;

use Predis\Helpers;

class PubSubSubscribe extends Command {
    public function getId() {
        return 'SUBSCRIBE';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterArrayArguments($arguments);
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return false;
    }
}
