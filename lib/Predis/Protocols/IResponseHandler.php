<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionSingle;

interface IResponseHandler {
    function handle(IConnectionSingle $connection, $payload);
}
