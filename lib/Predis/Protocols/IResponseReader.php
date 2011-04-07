<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionComposable;

interface IResponseReader {
    public function read(IConnectionComposable $connection);
}
