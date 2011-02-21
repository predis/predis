<?php

namespace Predis\Protocols;

use Predis\ICommand;

interface ICommandSerializer {
    public function serialize(ICommand $command);
}
