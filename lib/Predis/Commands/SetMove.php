<?php

namespace Predis\Commands;

class SetMove extends Command {
    public function getId() {
        return 'SMOVE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::skipLastArgument($arguments, $prefix);
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
