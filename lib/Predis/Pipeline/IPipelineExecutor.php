<?php

namespace Predis\Pipeline;

use Predis\Network\IConnection;

interface IPipelineExecutor {
    public function execute(IConnection $connection, &$commands);
}
