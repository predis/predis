<?php

namespace Predis\Protocols\Text;

use Predis\ServerException;
use Predis\Protocols\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseErrorHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $errorMessage) {
        throw new ServerException(substr($errorMessage, 4));
    }
}
