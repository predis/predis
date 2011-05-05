<?php

namespace Predis\Commands;

class ListPopLastPushHead extends Command {
    public function getId() {
        return 'RPOPLPUSH';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
}
