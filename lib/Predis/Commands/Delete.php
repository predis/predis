<?php

namespace Predis\Commands;

use Predis\Utils;

class Delete extends Command {
    public function getCommandId() { return 'DEL'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
