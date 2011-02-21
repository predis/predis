<?php

namespace Predis\Network;

use Predis\ICommand;
use Predis\Protocols\IRedisProtocol;

interface IConnectionSingle extends IConnection {
    public function getParameters();
    public function getProtocol();
    public function setProtocol(IRedisProtocol $protocol);
    public function __toString();
    public function writeBytes($buffer);
    public function readBytes($length);
    public function readLine();
    public function pushInitCommand(ICommand $command);
}
