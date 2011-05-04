<?php

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

class KeyPrefixProcessor implements ICommandProcessor {
    private $_prefix;

    public function __construct($prefix) {
        $this->setPrefix($prefix);
    }

    public function setPrefix($prefix) {
        $this->_prefix = $prefix;
    }

    public function getPrefix() {
        return $this->_prefix;
    }

    public function process(ICommand $command) {
        $command->prefixKeys($this->_prefix);
    }
}
