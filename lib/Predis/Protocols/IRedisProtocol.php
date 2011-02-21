<?php

namespace Predis\Protocols;

use Predis\ICommand;
use Predis\Network\IConnectionSingle;

interface IRedisProtocol {
    public function write(IConnectionSingle $connection, ICommand $command);
    public function read(IConnectionSingle $connection);
    public function setOption($option, $value);
}
