<?php

namespace Predis\Commands;

class ListPushTailV24x extends Command {
    public function getId() {
        return 'RPUSH';
    }

    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }
}
