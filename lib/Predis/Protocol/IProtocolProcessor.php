<?php

namespace Predis\Protocol;

use Predis\Commands\ICommand;
use Predis\Network\IConnectionComposable;

interface IProtocolProcessor extends IResponseReader {
    public function write(IConnectionComposable $connection, ICommand $command);
    public function setOption($option, $value);
}
