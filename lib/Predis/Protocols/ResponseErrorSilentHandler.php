<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionSingle;

class ResponseErrorSilentHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $errorMessage) {
        return new \Predis\ResponseError(substr($errorMessage, 4));
    }
}
