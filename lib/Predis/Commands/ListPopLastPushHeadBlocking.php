<?php

namespace Predis\Commands;

class ListPopLastPushHeadBlocking extends Command {
    public function getId() {
        return 'BRPOPLPUSH';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        return PrefixHelpers::skipLastArgument($arguments, $prefix);
    }

    protected function canBeHashed() {
        return $this->checkSameHashForKeys(
            array_slice($args = $this->getArguments(), 0, count($args) - 1)
        );
    }
}
