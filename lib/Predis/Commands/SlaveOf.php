<?php

namespace Predis\Commands;

class SlaveOf extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}
