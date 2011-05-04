<?php

namespace Predis\Commands;

class KeyRename extends Command {
    public function getId() {
        return 'RENAME';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return false;
    }
}
