<?php

namespace Predis\Commands;

class SetDifference extends SetIntersection {
    public function getId() {
        return 'SDIFF';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::multipleKeys($arguments, $prefix);
    }
}
