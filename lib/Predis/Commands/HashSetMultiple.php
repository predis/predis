<?php

namespace Predis\Commands;

use Predis\Command;

class HashSetMultiple extends Command {
    public function getCommandId() { return 'HMSET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = $arguments[1];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}
