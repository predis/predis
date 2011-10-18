<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Commands\ICommand;

interface IConnection
{
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(ICommand $command);
    public function readResponse(ICommand $command);
    public function executeCommand(ICommand $command);
}
