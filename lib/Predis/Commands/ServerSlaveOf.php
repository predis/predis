<?php

namespace Predis\Commands;

class ServerSlaveOf extends Command {
    public function getId() {
        return 'SLAVEOF';
    }

    protected function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }
}
