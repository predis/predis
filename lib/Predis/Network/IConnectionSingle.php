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

interface IConnectionSingle extends IConnection
{
    public function __toString();
    public function getResource();
    public function getParameters();
    public function pushInitCommand(ICommand $command);
    public function read();
}
