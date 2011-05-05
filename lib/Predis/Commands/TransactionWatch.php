<?php

namespace Predis\Commands;

class TransactionWatch extends Command {
    public function getId() {
        return 'WATCH';
    }

    protected function filterArguments(Array $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
