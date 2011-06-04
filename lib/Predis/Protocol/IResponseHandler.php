<?php

namespace Predis\Protocol;

use Predis\Network\IConnectionComposable;

interface IResponseHandler {
    function handle(IConnectionComposable $connection, $payload);
}
