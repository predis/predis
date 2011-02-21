<?php

namespace Predis\Commands;

use Predis\Command;

class Watch extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'WATCH'; }
    public function filterArguments(Array $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }
    public function parseResponse($data) { return (bool) $data; }
}
