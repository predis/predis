<?php

namespace Predis\Commands;

use Predis\Utils;

class SetIntersection extends Command {
    public function getCommandId() { return 'SINTER'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
