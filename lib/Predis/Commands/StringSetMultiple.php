<?php

namespace Predis\Commands;

class StringSetMultiple extends Command {
    public function getId() {
        return 'MSET';
    }

    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $flattenedKVs = array();
            $args = $arguments[0];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }

    protected function canBeHashed() {
        $args = $this->getArguments();
        $keys = array();
        for ($i = 0; $i < count($args); $i += 2) {
            $keys[] = $args[$i];
        }
        return $this->checkSameHashForKeys($keys);
    }
}
