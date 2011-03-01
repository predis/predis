<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionComposable;

interface IResponseHandler {
    function handle(IConnectionComposable $connection, $payload);
}
