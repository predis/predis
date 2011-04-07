<?php

namespace Predis\Protocols\Text;

use Predis\ResponseError;
use Predis\Protocols\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseErrorSilentHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $errorMessage) {
        return new ResponseError(substr($errorMessage, 4));
    }
}
