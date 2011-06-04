<?php

namespace Predis\Protocol\Text;

use Predis\ResponseError;
use Predis\Protocol\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseErrorSilentHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $errorMessage) {
        return new ResponseError($errorMessage);
    }
}
