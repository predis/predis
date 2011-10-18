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

interface IConnectionCluster extends IConnection
{
    public function add(IConnectionSingle $connection);
    public function getConnection(ICommand $command);
    public function getConnectionById($connectionId);
}
