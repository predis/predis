<?php

namespace Predis\Protocol;

use Predis\Commands\ICommand;

interface ICommandSerializer {
    public function serialize(ICommand $command);
}
