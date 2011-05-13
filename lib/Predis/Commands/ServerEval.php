<?php

namespace Predis\Commands;

class ServerEval extends Command {
    public function getId() {
        return 'EVAL';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        $arguments = $this->getArguments();
        for ($i = 2; $i < $arguments[1] + 2; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }
        return $arguments;
    }

    protected function canBeHashed() {
        return false;
    }
}
