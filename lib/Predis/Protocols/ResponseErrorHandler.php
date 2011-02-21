<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionSingle;

class ResponseErrorHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $errorMessage) {
        throw new \Predis\ServerException(substr($errorMessage, 4));
    }
}
