<?php

namespace Predis\Commands;

use Predis\Utils;
use Predis\Command;

class Delete extends Command {
    public function getCommandId() { return 'DEL'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
