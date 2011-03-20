<?php

namespace Predis\Network;

use Predis\Commands\ICommand;

interface IConnectionSingle extends IConnection {
    public function __toString();
    public function getResource();
    public function getParameters();
    public function pushInitCommand(ICommand $command);
    public function read();
}
