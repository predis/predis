<?php

namespace Predis\Protocol\Text;

use Predis\ServerException;
use Predis\Protocol\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseErrorHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $errorMessage) {
        throw new ServerException($errorMessage);
    }
}
