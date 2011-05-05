<?php

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

interface ICommandProcessor {
    public function process(ICommand $command);
}
