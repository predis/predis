<?php

namespace Predis\Protocol;

use Predis\Network\IConnectionComposable;

interface IResponseReader {
    public function read(IConnectionComposable $connection);
}
