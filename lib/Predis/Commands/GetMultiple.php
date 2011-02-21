<?php

namespace Predis\Commands;

use Predis\Utils;
use Predis\Command;

class GetMultiple extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
