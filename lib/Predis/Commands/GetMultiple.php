<?php

namespace Predis\Commands;

use Predis\Utils;

class GetMultiple extends Command {
    protected function canBeHashed() {
        return $this->checkSameHashForKeys($this->getArguments());
    }
    public function getId() { return 'MGET'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}
