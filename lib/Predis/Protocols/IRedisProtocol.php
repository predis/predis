<?php

namespace Predis\Protocols;

use Predis\ICommand;
use Predis\Network\IConnectionComposable;

interface IRedisProtocol {
    public function write(IConnectionComposable $connection, ICommand $command);
    public function read(IConnectionComposable $connection);
    public function setOption($option, $value);
}
