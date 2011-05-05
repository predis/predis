<?php

namespace Predis\Commands;

use Predis\Helpers;

class KeyDelete extends Command {
    public function getId() {
        return 'DEL';
    }

    protected function filterArguments(Array $arguments) {
        return Helpers::filterArrayArguments($arguments);
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        $args = $this->getArguments();
        if (count($args) === 1) {
            return true;
        }
        return $this->checkSameHashForKeys($args);
    }
}
