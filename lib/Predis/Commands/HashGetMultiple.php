<?php

namespace Predis\Commands;

class HashGetMultiple extends Command {
    public function getId() { return 'HMGET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = $arguments[1];
            foreach ($args as $v) {
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}
