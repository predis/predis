<?php

namespace Predis\Protocols;

use Predis\ServerException;
use Predis\Network\IConnectionComposable;

class ResponseErrorHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $errorMessage) {
        throw new ServerException(substr($errorMessage, 4));
    }
}
