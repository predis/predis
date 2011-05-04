<?php

namespace Predis\Commands;

class PubSubUnsubscribe extends Command {
    public function getId() {
        return 'UNSUBSCRIBE';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return false;
    }
}
