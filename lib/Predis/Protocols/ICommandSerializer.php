<?php

namespace Predis\Protocols;

use Predis\Commands\ICommand;

interface ICommandSerializer {
    public function serialize(ICommand $command);
}
