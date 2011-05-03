<?php

namespace Predis\Commands;

abstract class ScriptedCommand extends ServerEval {
    public abstract function getScript();

    public abstract function keysCount();

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
