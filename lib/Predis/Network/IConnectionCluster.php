<?php

namespace Predis\Network;

use Predis\Commands\ICommand;

interface IConnectionCluster extends IConnection {
    public function add(IConnectionSingle $connection);
    public function getConnection(ICommand $command);
    public function getConnectionById($connectionId);
}
