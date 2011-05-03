<?php

namespace Predis\Commands;

abstract class ScriptedCommand extends ServerEval {
    public abstract function getScript();

    protected function keysCount() {
        // The default behaviour is to use the first argument as the only value
        // for KEYS and the rest of the arguments (if any) for ARGV. When -1 is
        // returned, all the arguments are considered as values for KEYS.
        return 1;
    }

    protected function filterArguments(Array $arguments) {
        if (($keys = $this->keysCount()) === -1) {
            $keys = count($arguments);
        }
        return array_merge(array($this->getScript(), $keys), $arguments);
    }

    protected function getKeys() {
        $arguments = $this->getArguments();
        if (($keys = $this->keysCount()) === -1) {
            return array_slice($arguments, 2);
        }
        return array_slice($arguments, 2, $keys);
    }
}
