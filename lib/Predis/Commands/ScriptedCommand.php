<?php

namespace Predis\Commands;

abstract class ScriptedCommand extends ServerEval {
    public abstract function getScript();

    protected function keysCount() {
        // The default behaviour for the base class is to use all the arguments
        // passed to a scripted command to populate the KEYS table in Lua.
        return count($this->getArguments());
    }

    protected function filterArguments(Array $arguments) {
        return array_merge(array($this->getScript(), $this->keysCount()), $arguments);
    }

    protected function getKeys() {
        return array_slice($this->getArguments(), 2, $this->keysCount());
    }
}
