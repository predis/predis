<?php

namespace Predis\Network;

use Predis\Commands\ICommand;

interface IConnection {
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(ICommand $command);
    public function readResponse(ICommand $command);
    public function executeCommand(ICommand $command);
}
