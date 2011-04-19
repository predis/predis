<?php

namespace Predis\Commands;

class HashDeleteV24x extends Command {
    public function getId() {
        return 'HDEL';
    }

    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
