<?php

namespace Predis\Protocols;

use Predis\Commands\ICommand;
use Predis\Network\IConnectionComposable;

interface IProtocolProcessor extends IResponseReader {
    public function write(IConnectionComposable $connection, ICommand $command);
    public function setOption($option, $value);
}
