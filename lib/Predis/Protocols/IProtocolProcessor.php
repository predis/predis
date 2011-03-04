<?php

namespace Predis\Protocols;

use Predis\Commands\ICommand;
use Predis\Network\IConnectionComposable;

interface IProtocolProcessor {
    public function write(IConnectionComposable $connection, ICommand $command);
    public function read(IConnectionComposable $connection);
    public function setOption($option, $value);
}
