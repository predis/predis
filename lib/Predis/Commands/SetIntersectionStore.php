<?php

namespace Predis\Commands;

class SetIntersectionStore extends Command {
    public function getId() {
        return 'SINTERSTORE';
    }

    protected function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
}
