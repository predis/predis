<?php

namespace Predis\Commands;

use Predis\Utils;

class SetIntersectionStore extends Command {
    public function getCommandId() { return 'SINTERSTORE'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
